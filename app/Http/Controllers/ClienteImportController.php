<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmarExtraccionFotoClientesRequest;
use App\Http\Requests\ConfirmarImportacionClientesRequest;
use App\Http\Requests\SubirExcelClientesRequest;
use App\Http\Requests\SubirFotoClientesRequest;
use App\Models\Cliente;
use App\Models\ImportacionClienteCambio;
use App\Models\ImportacionClientes;
use App\Models\MapeoImportacionClientes;
use App\Services\ClaudeVision\ClaudeVisionApiClient;
use App\Services\ClaudeVision\Exceptions\ExtraccionFotoException;
use App\Services\GeneradorExcelClientesDesdeExtraccionService;
use App\Services\LectorArchivoExcel;
use App\Services\MapeoColumnasClienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        private readonly ClaudeVisionApiClient $claudeVision,
        private readonly GeneradorExcelClientesDesdeExtraccionService $generadorExcel,
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

        return $this->previsualizarDesdeArchivo(
            $token,
            $rutaRelativa,
            Storage::disk('local')->path($rutaRelativa),
            $archivo->getClientOriginalName(),
            $request->user()->empresa_id,
        );
    }

    /**
     * Punto compartido entre la importación por Excel subido a mano y la
     * importación por foto — ver ProductoImportController::previsualizarDesdeArchivo()
     * para el mismo razonamiento. A partir de un archivo que YA está en
     * disco, arma la pantalla de mapeo de columnas.
     */
    private function previsualizarDesdeArchivo(
        string $token,
        string $rutaRelativa,
        string $rutaAbsoluta,
        string $nombreArchivoOriginal,
        int $empresaId,
    ): View {
        $lectura = $this->lector->leerEncabezadosYPreview($rutaAbsoluta);
        $filasTotales = count($this->lector->leerFilas($rutaAbsoluta));

        $mapeoRecordado = MapeoImportacionClientes::where('empresa_id', $empresaId)->first()?->mapeo;

        $sugerencias = $this->mapeoColumnas->sugerir($lectura['encabezados'], $mapeoRecordado);

        Cache::put("import-clientes:{$token}", [
            'empresa_id' => $empresaId,
            'ruta' => $rutaRelativa,
            'encabezados' => $lectura['encabezados'],
            'nombre_archivo' => $nombreArchivoOriginal,
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

    public function subirFoto(): View
    {
        abort_if(! $this->claudeVision->configurada(), 404);

        return view('clientes.importar.foto-subir');
    }

    public function extraerFoto(SubirFotoClientesRequest $request): View
    {
        abort_if(! $this->claudeVision->configurada(), 404);

        $foto = $request->file('foto');
        $rutaTemporal = $foto->getRealPath();

        $this->redimensionarSiHaceFalta($rutaTemporal);

        try {
            $clientes = $this->claudeVision->extraerClientesDeFoto(
                file_get_contents($rutaTemporal),
                $foto->getMimeType(),
            );
        } catch (ExtraccionFotoException $e) {
            return back()->withErrors(['foto' => $e->getMessage()])->withInput();
        }

        return view('clientes.importar.foto-revisar', [
            'clientes' => $clientes,
            'campos' => MapeoColumnasClienteService::CAMPOS,
        ]);
    }

    /**
     * Mismo criterio y mismo valor (1568px) que
     * ProductoImportController::redimensionarSiHaceFalta() — ver ese
     * método para el razonamiento completo.
     */
    private function redimensionarSiHaceFalta(string $rutaAbsoluta, int $maxLado = 1568): void
    {
        $medidas = @getimagesize($rutaAbsoluta);

        if ($medidas === false || max($medidas[0], $medidas[1]) <= $maxLado) {
            return;
        }

        [$ancho, $alto] = $medidas;
        $original = imagecreatefromstring((string) file_get_contents($rutaAbsoluta));

        if ($original === false) {
            return;
        }

        $escala = $maxLado / max($ancho, $alto);
        $redimensionada = imagescale($original, (int) round($ancho * $escala), (int) round($alto * $escala));
        imagedestroy($original);

        if ($redimensionada === false) {
            return;
        }

        imagejpeg($redimensionada, $rutaAbsoluta, 85);
        imagedestroy($redimensionada);
    }

    public function confirmarExtraccion(ConfirmarExtraccionFotoClientesRequest $request): View
    {
        abort_if(! $this->claudeVision->configurada(), 404);

        $token = (string) Str::uuid();
        $rutaRelativa = "imports-temp/{$token}.xlsx";

        Storage::disk('local')->makeDirectory('imports-temp');
        $rutaAbsoluta = Storage::disk('local')->path($rutaRelativa);

        $spreadsheet = $this->generadorExcel->generar($request->validated('clientes'));
        (new Xlsx($spreadsheet))->save($rutaAbsoluta);

        return $this->previsualizarDesdeArchivo(
            $token,
            $rutaRelativa,
            $rutaAbsoluta,
            'Foto de lista de clientes.xlsx',
            $request->user()->empresa_id,
        );
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
