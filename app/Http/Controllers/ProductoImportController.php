<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmarImportacionProductosRequest;
use App\Http\Requests\SubirExcelProductosRequest;
use App\Models\ImportacionProductoCambio;
use App\Models\ImportacionProductos;
use App\Models\MapeoImportacionProductos;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\LectorArchivoExcel;
use App\Services\MapeoColumnasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductoImportController extends Controller
{
    public function __construct(
        private readonly LectorArchivoExcel $importador,
        private readonly MapeoColumnasService $mapeoColumnas,
    ) {}

    public function subir(Request $request): View
    {
        $proveedores = Proveedor::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('productos.importar.subir', ['proveedores' => $proveedores]);
    }

    public function previsualizar(SubirExcelProductosRequest $request): View
    {
        $archivo = $request->file('archivo');
        $token = (string) Str::uuid();
        $extension = $archivo->getClientOriginalExtension();

        $rutaRelativa = $archivo->storeAs('imports-temp', "{$token}.{$extension}", 'local');
        $rutaAbsoluta = Storage::disk('local')->path($rutaRelativa);

        $lectura = $this->importador->leerEncabezadosYPreview($rutaAbsoluta);
        $filasTotales = count($this->importador->leerFilas($rutaAbsoluta));

        $empresaId = $request->user()->empresa_id;
        $mapeoRecordado = MapeoImportacionProductos::where('empresa_id', $empresaId)->first()?->mapeo;

        $sugerencias = $this->mapeoColumnas->sugerir($lectura['encabezados'], $mapeoRecordado);

        $proveedorId = $request->validated('proveedor_id');
        $proveedorNombre = $proveedorId ? Proveedor::find($proveedorId)?->nombre : null;

        Cache::put("import-productos:{$token}", [
            'empresa_id' => $empresaId,
            'ruta' => $rutaRelativa,
            'encabezados' => $lectura['encabezados'],
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'proveedor_id' => $proveedorId,
        ], now()->addMinutes(30));

        return view('productos.importar.confirmar', [
            'token' => $token,
            'encabezados' => $lectura['encabezados'],
            'preview' => $lectura['preview'],
            'sugerencias' => $sugerencias,
            'campos' => MapeoColumnasService::CAMPOS,
            'filasTotales' => $filasTotales,
            'proveedorNombre' => $proveedorNombre,
        ]);
    }

    public function confirmar(ConfirmarImportacionProductosRequest $request): View|RedirectResponse
    {
        $datosImportacion = $request->datosImportacion();

        if (! $datosImportacion) {
            return redirect()->route('productos.importar.subir')
                ->withErrors(['token' => 'La importación expiró. Volvé a subir el archivo.']);
        }

        $empresaId = $datosImportacion['empresa_id'];
        $proveedorId = $datosImportacion['proveedor_id'] ?? null;
        $mapeoPorIndice = $request->validated('mapeo');
        $rutaAbsoluta = Storage::disk('local')->path($datosImportacion['ruta']);

        $filas = $this->importador->leerFilas($rutaAbsoluta);

        $creados = 0;
        $actualizados = 0;
        $errores = [];

        $importacion = ImportacionProductos::create([
            'empresa_id' => $empresaId,
            'user_id' => $request->user()->id,
            'proveedor_id' => $proveedorId,
            'nombre_archivo' => $datosImportacion['nombre_archivo'] ?? 'archivo.xlsx',
        ]);

        DB::transaction(function () use ($filas, $mapeoPorIndice, $empresaId, $proveedorId, $importacion, &$creados, &$actualizados, &$errores) {
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

                if (! is_numeric($datos['precio'] ?? null)) {
                    $errores[] = "Fila {$numeroFilaExcel}: el precio no es un número válido.";

                    continue;
                }

                $atributos = [
                    'nombre' => trim((string) $datos['nombre']),
                    'precio' => (float) $datos['precio'],
                    'costo' => is_numeric($datos['costo'] ?? null) ? (float) $datos['costo'] : null,
                    'stock' => is_numeric($datos['stock'] ?? null) ? (int) $datos['stock'] : 0,
                    'categoria' => blank($datos['categoria'] ?? null) ? null : trim((string) $datos['categoria']),
                    'marca' => blank($datos['marca'] ?? null) ? null : trim((string) $datos['marca']),
                    'unidad' => blank($datos['unidad'] ?? null) ? null : trim((string) $datos['unidad']),
                    'proveedor_id' => $proveedorId,
                ];

                $codigo = blank($datos['codigo'] ?? null) ? null : trim((string) $datos['codigo']);

                $producto = $codigo !== null
                    ? Producto::where('empresa_id', $empresaId)->where('codigo', $codigo)->first()
                    : null;

                if ($producto) {
                    $datosAnteriores = $producto->only([
                        'nombre', 'codigo', 'precio', 'costo', 'stock', 'categoria', 'marca', 'unidad', 'proveedor_id',
                    ]);

                    $producto->update($atributos);
                    $actualizados++;

                    ImportacionProductoCambio::create([
                        'importacion_id' => $importacion->id,
                        'producto_id' => $producto->id,
                        'fue_creado' => false,
                        'datos_anteriores' => $datosAnteriores,
                    ]);

                    continue;
                }

                $nuevoProducto = Producto::create([...$atributos, 'empresa_id' => $empresaId, 'codigo' => $codigo]);
                $creados++;

                ImportacionProductoCambio::create([
                    'importacion_id' => $importacion->id,
                    'producto_id' => $nuevoProducto->id,
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

        MapeoImportacionProductos::updateOrCreate(
            ['empresa_id' => $empresaId],
            ['mapeo' => $this->construirMapeoParaGuardar($datosImportacion['encabezados'], $mapeoPorIndice)]
        );

        Storage::disk('local')->delete($datosImportacion['ruta']);
        Cache::forget('import-productos:'.$request->input('token'));

        return view('productos.importar.resultado', [
            'creados' => $creados,
            'actualizados' => $actualizados,
            'errores' => $errores,
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
