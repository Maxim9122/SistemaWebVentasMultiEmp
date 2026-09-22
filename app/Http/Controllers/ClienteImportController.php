<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmarImportacionClientesRequest;
use App\Http\Requests\SubirExcelClientesRequest;
use App\Models\Cliente;
use App\Models\ImportacionClienteCambio;
use App\Models\ImportacionClientes;
use App\Models\MapeoImportacionClientes;
use App\Services\LectorArchivoExcel;
use App\Services\MapeoColumnasClienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Mismo flujo en dos pasos (previsualizar con mapeo sugerido + confirmar) que
 * `ProductoImportController` — ver ese archivo para el razonamiento completo
 * del diseño (mapeo automático + memoria por empresa + historial/deshacer).
 * Acá la clave para actualizar-en-vez-de-crear es el CUIT (único por
 * empresa), en vez del código de producto.
 */
class ClienteImportController extends Controller
{
    public function __construct(
        private readonly LectorArchivoExcel $lector,
        private readonly MapeoColumnasClienteService $mapeoColumnas,
    ) {}

    public function subir(): View
    {
        return view('clientes.importar.subir');
    }

    public function previsualizar(SubirExcelClientesRequest $request): View
    {
        $archivo = $request->file('archivo');
        $token = (string) Str::uuid();
        $extension = $archivo->getClientOriginalExtension();

        $rutaRelativa = $archivo->storeAs('imports-temp', "{$token}.{$extension}", 'local');
        $rutaAbsoluta = Storage::disk('local')->path($rutaRelativa);

        $lectura = $this->lector->leerEncabezadosYPreview($rutaAbsoluta);
        $filasTotales = count($this->lector->leerFilas($rutaAbsoluta));

        $empresaId = $request->user()->empresa_id;
        $mapeoRecordado = MapeoImportacionClientes::where('empresa_id', $empresaId)->first()?->mapeo;

        $sugerencias = $this->mapeoColumnas->sugerir($lectura['encabezados'], $mapeoRecordado);

        Cache::put("import-clientes:{$token}", [
            'empresa_id' => $empresaId,
            'ruta' => $rutaRelativa,
            'encabezados' => $lectura['encabezados'],
            'nombre_archivo' => $archivo->getClientOriginalName(),
        ], now()->addMinutes(30));

        return view('clientes.importar.confirmar', [
            'token' => $token,
            'encabezados' => $lectura['encabezados'],
            'preview' => $lectura['preview'],
            'sugerencias' => $sugerencias,
            'campos' => MapeoColumnasClienteService::CAMPOS,
            'filasTotales' => $filasTotales,
        ]);
    }

    public function confirmar(ConfirmarImportacionClientesRequest $request): View|RedirectResponse
    {
        $datosImportacion = $request->datosImportacion();

        if (! $datosImportacion) {
            return redirect()->route('clientes.importar.subir')
                ->withErrors(['token' => 'La importación expiró. Volvé a subir el archivo.']);
        }

        $empresaId = $datosImportacion['empresa_id'];
        $mapeoPorIndice = $request->validated('mapeo');
        $rutaAbsoluta = Storage::disk('local')->path($datosImportacion['ruta']);

        $filas = $this->lector->leerFilas($rutaAbsoluta);

        $creados = 0;
        $actualizados = 0;
        $errores = [];
        // Cuántas filas importadas/actualizadas quedaron sin CUIT/teléfono/
        // email — no bloquean la importación (es normal no tener todos los
        // datos de todos los clientes), pero se avisan como resumen al final.
        $faltantes = ['cuit' => 0, 'telefono' => 0, 'email' => 0];

        $importacion = ImportacionClientes::create([
            'empresa_id' => $empresaId,
            'user_id' => $request->user()->id,
            'nombre_archivo' => $datosImportacion['nombre_archivo'] ?? 'archivo.xlsx',
        ]);

        DB::transaction(function () use ($filas, $mapeoPorIndice, $empresaId, $importacion, &$creados, &$actualizados, &$errores, &$faltantes) {
            foreach ($filas as $indiceFila => $fila) {
                if (collect($fila)->every(fn ($valor) => trim((string) $valor) === '')) {
                    continue;
                }

                $numeroFilaExcel = $indiceFila + 2;
                $datos = $this->extraerDatosFila($fila, $mapeoPorIndice);

                if (blank($datos['nombre'] ?? null)) {
                    $errores[] = "Fila {$numeroFilaExcel}: falta el nombre.";

                    continue;
                }

                // Sin CUIT/teléfono/email no es un error — es normal que un
                // Excel de clientes no tenga todos los datos de todos. Sin
                // CUIT sí significa que esta fila nunca puede "actualizar" a
                // un cliente existente (no hay con qué identificarlo): entra
                // siempre como alta nueva.
                $cuit = blank($datos['cuit'] ?? null) ? null : trim((string) $datos['cuit']);
                $telefono = blank($datos['telefono'] ?? null) ? null : trim((string) $datos['telefono']);
                $email = blank($datos['email'] ?? null) ? null : trim((string) $datos['email']);

                if ($cuit === null) {
                    $faltantes['cuit']++;
                }

                if ($telefono === null) {
                    $faltantes['telefono']++;
                }

                if ($email === null) {
                    $faltantes['email']++;
                }

                $atributos = [
                    'nombre' => trim((string) $datos['nombre']),
                    'cuit' => $cuit,
                    'telefono' => $telefono,
                    'email' => $email,
                ];

                $cliente = $cuit !== null
                    ? Cliente::where('empresa_id', $empresaId)->where('cuit', $cuit)->first()
                    : null;

                if ($cliente) {
                    $datosAnteriores = $cliente->only(['nombre', 'cuit', 'telefono', 'email']);

                    $cliente->update($atributos);
                    $actualizados++;

                    ImportacionClienteCambio::create([
                        'importacion_id' => $importacion->id,
                        'cliente_id' => $cliente->id,
                        'fue_creado' => false,
                        'datos_anteriores' => $datosAnteriores,
                    ]);

                    continue;
                }

                $nuevoCliente = Cliente::create([...$atributos, 'empresa_id' => $empresaId, 'activo' => true]);
                $creados++;

                ImportacionClienteCambio::create([
                    'importacion_id' => $importacion->id,
                    'cliente_id' => $nuevoCliente->id,
                    'fue_creado' => true,
                    'datos_anteriores' => null,
                ]);
            }
        });

        $importacion->update([
            'creados_count' => $creados,
            'actualizados_count' => $actualizados,
            'errores_count' => count($errores),
        ]);

        MapeoImportacionClientes::updateOrCreate(
            ['empresa_id' => $empresaId],
            ['mapeo' => $this->construirMapeoParaGuardar($datosImportacion['encabezados'], $mapeoPorIndice)]
        );

        Storage::disk('local')->delete($datosImportacion['ruta']);
        Cache::forget('import-clientes:'.$request->input('token'));

        return view('clientes.importar.resultado', [
            'creados' => $creados,
            'actualizados' => $actualizados,
            'errores' => $errores,
            'faltantes' => $faltantes,
        ]);
    }

    /**
     * @param  list<mixed>  $fila
     * @param  array<int|string, string|null>  $mapeoPorIndice
     * @return array<string, mixed>
     */
    private function extraerDatosFila(array $fila, array $mapeoPorIndice): array
    {
        $datos = [];

        foreach ($mapeoPorIndice as $indice => $campo) {
            if (blank($campo)) {
                continue;
            }

            $datos[$campo] = $fila[$indice] ?? null;
        }

        return $datos;
    }

    /**
     * @param  list<string>  $encabezados
     * @param  array<int|string, string|null>  $mapeoPorIndice
     * @return array<string, string>
     */
    private function construirMapeoParaGuardar(array $encabezados, array $mapeoPorIndice): array
    {
        $mapeo = [];

        foreach ($mapeoPorIndice as $indice => $campo) {
            if (blank($campo)) {
                continue;
            }

            $normalizado = $this->mapeoColumnas->normalizar((string) ($encabezados[$indice] ?? ''));

            if ($normalizado !== '') {
                $mapeo[$normalizado] = $campo;
            }
        }

        return $mapeo;
    }
}
