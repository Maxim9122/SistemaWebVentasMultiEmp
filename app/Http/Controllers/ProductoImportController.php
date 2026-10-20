<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmarExtraccionFotoProductosRequest;
use App\Http\Requests\ConfirmarImportacionProductosRequest;
use App\Http\Requests\SubirExcelProductosRequest;
use App\Http\Requests\SubirFotoProductosRequest;
use App\Models\ImportacionProductoCambio;
use App\Models\ImportacionProductos;
use App\Models\MapeoImportacionProductos;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\ClaudeVision\ClaudeVisionApiClient;
use App\Services\ClaudeVision\Exceptions\ExtraccionFotoProductosException;
use App\Services\GeneradorExcelProductosDesdeExtraccionService;
use App\Services\LectorArchivoExcel;
use App\Services\MapeoColumnasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductoImportController extends Controller
{
    /**
     * Valores que Excel deja en una celda cuando el dato original se perdió
     * (columna corrupta, fórmula rota, recuperación automática de un archivo
     * dañado) — no son códigos reales, aunque no estén vacíos. Si se los
     * tratara como código de verdad, la primera fila con ese valor crea un
     * producto y todas las filas siguientes con el mismo valor lo van
     * pisando en cadena, mezclando productos distintos entre sí.
     */
    private const CODIGOS_INVALIDOS = [
        'nan', '#n/a', 'n/a', 'na', '#value!', '#ref!', '#div/0!', '#null!', '#name?', '#num!',
    ];

    public function __construct(
        private readonly LectorArchivoExcel $importador,
        private readonly MapeoColumnasService $mapeoColumnas,
        private readonly ClaudeVisionApiClient $claudeVision,
        private readonly GeneradorExcelProductosDesdeExtraccionService $generadorExcel,
    ) {}

    private function esCodigoInvalido(string $codigo): bool
    {
        return in_array(mb_strtolower(trim($codigo)), self::CODIGOS_INVALIDOS, true);
    }

    /**
     * Limpia un valor numérico tal cual puede venir de un Excel real: con
     * símbolo de moneda ("$100"), espacios ("$ 1.500"), separador de miles
     * y/o decimales en formato argentino ("$1.500,50") o en formato en
     * inglés ("1,500.50"). Devuelve un string que is_numeric() /  (float)
     * puedan procesar bien, o null si no se pudo interpretar como número.
     *
     * Para decidir qué es separador de miles y qué es decimal cuando el
     * valor solo trae UNO de los dos símbolos (ej "1.500" o "1,500", sin el
     * otro para desambiguar): si tiene exactamente 3 dígitos después del
     * símbolo, se asume separador de miles (así "1.500" da 1500, no 1.5);
     * con 1 o 2 dígitos, se asume decimal (así "99,90" da 99.90).
     */
    private function normalizarNumero(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        // Saca cualquier cosa que no sea dígito, coma, punto o signo menos
        // (símbolo de moneda, espacios, "ARS", etc.).
        $valor = preg_replace('/[^\d,.\-]/', '', $valor) ?? '';

        if ($valor === '' || $valor === '-') {
            return null;
        }

        $tienePunto = str_contains($valor, '.');
        $tieneComa = str_contains($valor, ',');

        if ($tienePunto && $tieneComa) {
            if (strrpos($valor, ',') > strrpos($valor, '.')) {
                $valor = str_replace(',', '.', str_replace('.', '', $valor));
            } else {
                $valor = str_replace(',', '', $valor);
            }
        } elseif ($tieneComa) {
            $valor = strlen(substr($valor, strrpos($valor, ',') + 1)) === 3
                ? str_replace(',', '', $valor)
                : str_replace(',', '.', $valor);
        } elseif ($tienePunto) {
            if (strlen(substr($valor, strrpos($valor, '.') + 1)) === 3) {
                $valor = str_replace('.', '', $valor);
            }
        }

        return is_numeric($valor) ? $valor : null;
    }

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

        return $this->previsualizarDesdeArchivo(
            $token,
            $rutaRelativa,
            Storage::disk('local')->path($rutaRelativa),
            $archivo->getClientOriginalName(),
            $request->user()->empresa_id,
            $request->validated('proveedor_id'),
        );
    }

    /**
     * Punto compartido entre la importación por Excel subido a mano y la
     * importación por foto (que genera un .xlsx sintético y lo mete por acá
     * — ver confirmarExtraccion()): a partir de un archivo que YA está en
     * disco, arma la pantalla de mapeo de columnas/duplicados. Nunca sabe ni
     * le importa de dónde salió el archivo.
     */
    private function previsualizarDesdeArchivo(
        string $token,
        string $rutaRelativa,
        string $rutaAbsoluta,
        string $nombreArchivoOriginal,
        int $empresaId,
        ?int $proveedorId,
    ): View {
        $lectura = $this->importador->leerEncabezadosYPreview($rutaAbsoluta);
        $filasTotales = count($this->importador->leerFilas($rutaAbsoluta));

        $mapeoRecordado = MapeoImportacionProductos::where('empresa_id', $empresaId)->first()?->mapeo;

        $sugerencias = $this->mapeoColumnas->sugerir($lectura['encabezados'], $mapeoRecordado);

        $proveedorNombre = $proveedorId ? Proveedor::find($proveedorId)?->nombre : null;

        Cache::put("import-productos:{$token}", [
            'empresa_id' => $empresaId,
            'ruta' => $rutaRelativa,
            'encabezados' => $lectura['encabezados'],
            'nombre_archivo' => $nombreArchivoOriginal,
            'proveedor_id' => $proveedorId,
        ], now()->addMinutes(30));

        // Según la asignación de columnas ya sugerida (el usuario todavía la
        // puede ajustar abajo) — es una estimación para avisar antes de
        // importar, no la fuente de verdad: confirmar() vuelve a calcularlo
        // con el mapeo definitivo que se haya terminado usando.
        $duplicados = $this->detectarDuplicados($rutaAbsoluta, $sugerencias, $empresaId);
        $codigosInvalidos = $this->detectarCodigosInvalidos($rutaAbsoluta, $sugerencias);

        return view('productos.importar.confirmar', [
            'token' => $token,
            'encabezados' => $lectura['encabezados'],
            'preview' => $lectura['preview'],
            'sugerencias' => $sugerencias,
            'campos' => MapeoColumnasService::CAMPOS,
            'filasTotales' => $filasTotales,
            'proveedorNombre' => $proveedorNombre,
            'duplicados' => $duplicados,
            'codigosInvalidos' => $codigosInvalidos,
        ]);
    }

    public function subirFoto(Request $request): View
    {
        abort_if(! $this->claudeVision->configurada(), 404);

        $proveedores = Proveedor::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('productos.importar.foto-subir', ['proveedores' => $proveedores]);
    }

    public function extraerFoto(SubirFotoProductosRequest $request): View
    {
        abort_if(! $this->claudeVision->configurada(), 404);

        $foto = $request->file('foto');
        $rutaTemporal = $foto->getRealPath();

        $this->redimensionarSiHaceFalta($rutaTemporal);

        try {
            $productos = $this->claudeVision->extraerProductosDeFoto(
                file_get_contents($rutaTemporal),
                $foto->getMimeType(),
            );
        } catch (ExtraccionFotoProductosException $e) {
            return back()->withErrors(['foto' => $e->getMessage()])->withInput();
        }

        return view('productos.importar.foto-revisar', [
            'productos' => $productos,
            'campos' => MapeoColumnasService::CAMPOS,
            'proveedorId' => $request->validated('proveedor_id'),
        ]);
    }

    /**
     * 1568px de lado más largo es lo que Anthropic documenta como el punto
     * óptimo costo/calidad para vision — una foto de celular sin comprimir
     * suele venir muy por arriba de eso. Reducirla antes de mandarla achica
     * el costo de la llamada y el tiempo de subida, sin perder legibilidad
     * para una lista de precios. GD ya está disponible, sin agregar
     * dependencia nueva (mismo motor que ya usa el QR de AFIP).
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

    public function confirmarExtraccion(ConfirmarExtraccionFotoProductosRequest $request): View
    {
        abort_if(! $this->claudeVision->configurada(), 404);

        $token = (string) Str::uuid();
        $rutaRelativa = "imports-temp/{$token}.xlsx";

        Storage::disk('local')->makeDirectory('imports-temp');
        $rutaAbsoluta = Storage::disk('local')->path($rutaRelativa);

        $spreadsheet = $this->generadorExcel->generar($request->validated('productos'));
        (new Xlsx($spreadsheet))->save($rutaAbsoluta);

        return $this->previsualizarDesdeArchivo(
            $token,
            $rutaRelativa,
            $rutaAbsoluta,
            'Foto de lista de precios.xlsx',
            $request->user()->empresa_id,
            $request->validated('proveedor_id'),
        );
    }

    /**
     * Cuenta, agrupado por valor, cuántas filas del Excel tienen un código
     * "inválido" (ver CODIGOS_INVALIDOS) — para avisar antes de importar que
     * esas filas se van a crear como productos nuevos sin código, en vez de
     * intentar buscarles una coincidencia por ese valor.
     *
     * @param  array<int, string|null>  $mapeo
     * @return array<string, int>
     */
    private function detectarCodigosInvalidos(string $rutaAbsoluta, array $mapeo): array
    {
        $indiceCodigo = array_search('codigo', $mapeo, true);

        if ($indiceCodigo === false) {
            return [];
        }

        $conteo = [];

        foreach ($this->importador->leerFilas($rutaAbsoluta) as $fila) {
            $codigo = trim((string) ($fila[$indiceCodigo] ?? ''));

            if ($codigo !== '' && $this->esCodigoInvalido($codigo)) {
                $conteo[$codigo] = ($conteo[$codigo] ?? 0) + 1;
            }
        }

        return $conteo;
    }

    /**
     * Productos del Excel cuyo código ya existe en el catálogo de la
     * empresa — se avisa antes de importar para que el usuario elija si
     * actualizarlos o dejarlos como están, en vez de pisarlos en silencio.
     *
     * Se agrupa por código (para no repetir la misma línea cientos de veces
     * si el Excel tiene ese código repetido), pero "veces" cuenta cuántas
     * filas reales del Excel tienen ese código — así el aviso no subestima
     * el impacto real de la importación.
     *
     * @param  array<int, string|null>  $mapeo
     * @return \Illuminate\Support\Collection<int, array{codigo: string, nombre_excel: string, nombre_actual: string, veces: int}>
     */
    private function detectarDuplicados(string $rutaAbsoluta, array $mapeo, int $empresaId): \Illuminate\Support\Collection
    {
        $indiceCodigo = array_search('codigo', $mapeo, true);
        $indiceNombre = array_search('nombre', $mapeo, true);

        if ($indiceCodigo === false) {
            return collect();
        }

        $filas = $this->importador->leerFilas($rutaAbsoluta);
        $vecesPorCodigo = [];
        $nombreExcelPorCodigo = [];

        foreach ($filas as $fila) {
            $codigo = trim((string) ($fila[$indiceCodigo] ?? ''));

            if ($codigo === '' || $this->esCodigoInvalido($codigo)) {
                continue;
            }

            $vecesPorCodigo[$codigo] = ($vecesPorCodigo[$codigo] ?? 0) + 1;

            if (! isset($nombreExcelPorCodigo[$codigo])) {
                $nombreExcelPorCodigo[$codigo] = $indiceNombre !== false ? trim((string) ($fila[$indiceNombre] ?? '')) : '';
            }
        }

        if ($vecesPorCodigo === []) {
            return collect();
        }

        return Producto::where('empresa_id', $empresaId)
            ->whereIn('codigo', array_keys($vecesPorCodigo))
            ->get(['codigo', 'nombre'])
            ->map(fn (Producto $producto) => [
                'codigo' => $producto->codigo,
                'nombre_excel' => $nombreExcelPorCodigo[$producto->codigo] ?? '',
                'nombre_actual' => $producto->nombre,
                'veces' => $vecesPorCodigo[$producto->codigo] ?? 1,
            ])
            ->values();
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
        $modoDuplicados = $request->validated('modo_duplicados') ?? 'actualizar';
        $rutaAbsoluta = Storage::disk('local')->path($datosImportacion['ruta']);

        $filas = $this->importador->leerFilas($rutaAbsoluta);

        $creados = 0;
        $actualizados = 0;
        $ignorados = 0;
        $copiados = 0;
        $codigosInvalidos = 0;
        $errores = [];

        $importacion = ImportacionProductos::create([
            'empresa_id' => $empresaId,
            'user_id' => $request->user()->id,
            'proveedor_id' => $proveedorId,
            'nombre_archivo' => $datosImportacion['nombre_archivo'] ?? 'archivo.xlsx',
        ]);

        DB::transaction(function () use ($filas, $mapeoPorIndice, $empresaId, $proveedorId, $modoDuplicados, $importacion, &$creados, &$actualizados, &$ignorados, &$copiados, &$codigosInvalidos, &$errores) {
            foreach ($filas as $indiceFila => $fila) {
                if (collect($fila)->every(fn ($valor) => trim((string) $valor) === '')) {
                    continue;
                }

                $numeroFilaExcel = $indiceFila + 2;
                $datos = $this->extraerDatosFila($fila, $mapeoPorIndice);

                foreach (['precio', 'costo', 'stock'] as $campoNumerico) {
                    if (array_key_exists($campoNumerico, $datos)) {
                        $datos[$campoNumerico] = $this->normalizarNumero($datos[$campoNumerico]);
                    }
                }

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

                if ($codigo !== null && $this->esCodigoInvalido($codigo)) {
                    // "NaN", "#N/A", etc. no son códigos reales — se tratan
                    // como si la celda estuviera vacía, para que nunca
                    // encuentren (ni generen) una coincidencia falsa con otra
                    // fila que tenga el mismo valor basura.
                    $codigo = null;
                    $codigosInvalidos++;
                }

                $producto = $codigo !== null
                    ? Producto::where('empresa_id', $empresaId)->where('codigo', $codigo)->first()
                    : null;

                if ($producto) {
                    if ($modoDuplicados === 'ignorar') {
                        $ignorados++;

                        continue;
                    }

                    if ($modoDuplicados === 'copiar') {
                        // Copia como producto nuevo, sin código: si le dejáramos
                        // el mismo código del Excel, chocaría con la restricción
                        // única (empresa_id, codigo) de la tabla. No se inventa
                        // un código al azar — directamente queda sin código,
                        // igual que cualquier producto cargado sin uno.
                        $copia = Producto::create([...$atributos, 'empresa_id' => $empresaId, 'codigo' => null]);
                        $copiados++;

                        ImportacionProductoCambio::create([
                            'importacion_id' => $importacion->id,
                            'producto_id' => $copia->id,
                            'fue_creado' => true,
                            'datos_anteriores' => null,
                        ]);

                        continue;
                    }

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
            'ignorados_count' => $ignorados,
            'copiados_count' => $copiados,
            'codigos_invalidos_count' => $codigosInvalidos,
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
            'ignorados' => $ignorados,
            'copiados' => $copiados,
            'codigosInvalidos' => $codigosInvalidos,
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
