<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmarExtraccionFotoProductosRequest;
use App\Http\Requests\ConfirmarImportacionProductosRequest;
use App\Http\Requests\ProcesarLoteImportacionProductosRequest;
use App\Http\Requests\SubirExcelProductosRequest;
use App\Http\Requests\SubirFotoProductosRequest;
use App\Http\Requests\SubirPdfProductosRequest;
use App\Models\ImportacionProductoCambio;
use App\Models\ImportacionProductos;
use App\Models\MapeoImportacionProductos;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\ClaudeVision\ClaudeVisionApiClient;
use App\Services\ClaudeVision\Exceptions\ExtraccionFotoException;
use App\Services\GeneradorExcelProductosDesdeExtraccionService;
use App\Services\LectorArchivoExcel;
use App\Services\MapeoColumnasService;
use App\Services\NormalizadorNumeroService;
use App\Services\PdfAImagenesService;
use Illuminate\Http\JsonResponse;
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

    /**
     * Cuántas filas procesa cada request de "importar/procesar-lote". Un
     * Excel de miles de filas, procesado entero en un solo request HTTP,
     * puede superar el timeout del gateway del servidor (504) — dividirlo
     * en pedidos más chicos, uno atrás del otro desde el navegador, evita
     * ese límite sin importar cuán grande sea el archivo, y como cada lote
     * corre en su propia transacción (corta), tampoco mantiene bloqueos de
     * base de datos por mucho tiempo mientras se importa.
     */
    private const TAMANO_LOTE_IMPORTACION = 300;

    public function __construct(
        private readonly LectorArchivoExcel $importador,
        private readonly MapeoColumnasService $mapeoColumnas,
        private readonly ClaudeVisionApiClient $claudeVision,
        private readonly GeneradorExcelProductosDesdeExtraccionService $generadorExcel,
        private readonly NormalizadorNumeroService $normalizador,
    ) {}

    private function esCodigoInvalido(string $codigo): bool
    {
        return in_array(mb_strtolower(trim($codigo)), self::CODIGOS_INVALIDOS, true);
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
        $lectura = $this->importador->leer($rutaAbsoluta, $this->mapeoColumnas->detectarFilaEncabezado(...));

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
        $duplicados = $this->detectarDuplicados($lectura['filas'], $sugerencias, $empresaId);
        $codigosInvalidos = $this->detectarCodigosInvalidos($lectura['filas'], $sugerencias);

        return view('productos.importar.confirmar', [
            'token' => $token,
            'encabezados' => $lectura['encabezados'],
            'preview' => $lectura['preview'],
            'sugerencias' => $sugerencias,
            'campos' => MapeoColumnasService::CAMPOS,
            'filasTotales' => count($lectura['filas']),
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
        } catch (ExtraccionFotoException $e) {
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

    public function subirPdf(Request $request): View
    {
        abort_if(! $this->claudeVision->configurada(), 404);

        $proveedores = Proveedor::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('productos.importar.pdf-subir', ['proveedores' => $proveedores]);
    }

    /**
     * Convierte cada página del PDF en una imagen (ver PdfAImagenesService)
     * y manda cada una, por separado, por el mismo extractor que ya usa
     * "Importar desde una foto" — se junta todo lo que haya salido de
     * cualquier página en una sola lista de productos para revisar.
     *
     * Si una página puntual no tiene ningún producto legible (una portada,
     * una hoja de condiciones, una página que no cargó bien), se la salta
     * en vez de cortar toda la importación por esa sola página — recién si
     * NINGUNA página dio productos se avisa el error.
     */
    public function extraerPdf(SubirPdfProductosRequest $request, PdfAImagenesService $pdfAImagenes): View
    {
        abort_if(! $this->claudeVision->configurada(), 404);

        $pdf = $request->file('pdf');
        $directorioTemporal = sys_get_temp_dir().'/pdf-import-'.Str::uuid();
        mkdir($directorioTemporal, 0700, true);

        try {
            $rutasImagenes = $pdfAImagenes->convertir($pdf->getRealPath(), $directorioTemporal);
        } catch (\RuntimeException $e) {
            @rmdir($directorioTemporal);

            return back()->withErrors(['pdf' => $e->getMessage()])->withInput();
        }

        $productos = [];

        try {
            foreach ($rutasImagenes as $rutaImagen) {
                $this->redimensionarSiHaceFalta($rutaImagen);

                try {
                    $productosPagina = $this->claudeVision->extraerProductosDeFoto(
                        file_get_contents($rutaImagen),
                        'image/jpeg',
                    );
                    array_push($productos, ...$productosPagina);
                } catch (ExtraccionFotoException) {
                    continue;
                }
            }
        } finally {
            foreach ($rutasImagenes as $rutaImagen) {
                @unlink($rutaImagen);
            }
            @rmdir($directorioTemporal);
        }

        if ($productos === []) {
            return back()->withErrors(['pdf' => 'No se detectó ningún producto en el PDF. Probá con otro archivo más claro.'])->withInput();
        }

        return view('productos.importar.foto-revisar', [
            'productos' => $productos,
            'campos' => MapeoColumnasService::CAMPOS,
            'proveedorId' => $request->validated('proveedor_id'),
            'rutaVolver' => route('productos.importarPdf.subir'),
            'textoVolver' => 'Volver a subir el PDF',
        ]);
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
     * @param  list<list<mixed>>  $filas
     * @return array<string, int>
     */
    private function detectarCodigosInvalidos(array $filas, array $mapeo): array
    {
        $indiceCodigo = array_search('codigo', $mapeo, true);

        if ($indiceCodigo === false) {
            return [];
        }

        $conteo = [];

        foreach ($filas as $fila) {
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
     * @param  list<list<mixed>>  $filas
     * @param  array<int, string|null>  $mapeo
     * @return \Illuminate\Support\Collection<int, array{codigo: string, nombre_excel: string, nombre_actual: string, veces: int}>
     */
    private function detectarDuplicados(array $filas, array $mapeo, int $empresaId): \Illuminate\Support\Collection
    {
        $indiceCodigo = array_search('codigo', $mapeo, true);
        $indiceNombre = array_search('nombre', $mapeo, true);

        if ($indiceCodigo === false) {
            return collect();
        }

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

    /**
     * Camino de respaldo sin JavaScript (o para archivos chicos, como los
     * que vienen de foto/PDF): procesa TODO de una sola vez, en un único
     * request. Para archivos grandes, el camino normal es procesarLote()
     * (ver más abajo), que divide el mismo trabajo en pedidos chicos desde
     * el navegador para no toparse con el timeout del servidor.
     */
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

        $filas = $this->importador->leer($rutaAbsoluta, $this->mapeoColumnas->detectarFilaEncabezado(...))['filas'];

        $importacion = ImportacionProductos::create([
            'empresa_id' => $empresaId,
            'user_id' => $request->user()->id,
            'proveedor_id' => $proveedorId,
            'nombre_archivo' => $datosImportacion['nombre_archivo'] ?? 'archivo.xlsx',
        ]);

        $resultado = DB::transaction(fn () => $this->procesarLoteFilas(
            $filas, 0, $mapeoPorIndice, $empresaId, $proveedorId, $modoDuplicados, $importacion,
        ));

        $importacion->update([
            'creados_count' => $resultado['creados'],
            'actualizados_count' => $resultado['actualizados'],
            'ignorados_count' => $resultado['ignorados'],
            'copiados_count' => $resultado['copiados'],
            'codigos_invalidos_count' => $resultado['codigosInvalidos'],
            'errores_count' => count($resultado['errores']),
        ]);

        MapeoImportacionProductos::updateOrCreate(
            ['empresa_id' => $empresaId],
            ['mapeo' => $this->construirMapeoParaGuardar($datosImportacion['encabezados'], $mapeoPorIndice)]
        );

        Storage::disk('local')->delete($datosImportacion['ruta']);
        Cache::forget('import-productos:'.$request->input('token'));

        return view('productos.importar.resultado', [
            'creados' => $resultado['creados'],
            'actualizados' => $resultado['actualizados'],
            'ignorados' => $resultado['ignorados'],
            'copiados' => $resultado['copiados'],
            'codigosInvalidos' => $resultado['codigosInvalidos'],
            'errores' => $resultado['errores'],
        ]);
    }

    /**
     * Camino principal (vía JavaScript, ver confirmar.blade.php): procesa
     * TAMANO_LOTE_IMPORTACION filas por pedido, acumulando en la misma
     * ImportacionProductos entre pedidos (se manda su id en $importacion_id
     * a partir del segundo lote). Nunca se topa con el timeout del gateway
     * sin importar cuán grande sea el archivo, porque cada pedido hace una
     * fracción chica y acotada del trabajo total.
     */
    public function procesarLote(ProcesarLoteImportacionProductosRequest $request): JsonResponse
    {
        $datosImportacion = $request->datosImportacion();

        if (! $datosImportacion) {
            return response()->json(['mensaje' => 'La importación expiró. Volvé a subir el archivo.'], 422);
        }

        $empresaId = $datosImportacion['empresa_id'];
        $proveedorId = $datosImportacion['proveedor_id'] ?? null;
        $mapeoPorIndice = $request->validated('mapeo');
        $modoDuplicados = $request->validated('modo_duplicados') ?? 'actualizar';
        $offset = (int) $request->validated('offset');
        $rutaAbsoluta = Storage::disk('local')->path($datosImportacion['ruta']);

        $filas = $this->importador->leer($rutaAbsoluta, $this->mapeoColumnas->detectarFilaEncabezado(...))['filas'];
        $total = count($filas);

        $importacionId = $request->validated('importacion_id');

        $importacion = $importacionId
            ? ImportacionProductos::where('empresa_id', $empresaId)->findOrFail($importacionId)
            : ImportacionProductos::create([
                'empresa_id' => $empresaId,
                'user_id' => $request->user()->id,
                'proveedor_id' => $proveedorId,
                'nombre_archivo' => $datosImportacion['nombre_archivo'] ?? 'archivo.xlsx',
                'creados_count' => 0,
                'actualizados_count' => 0,
                'ignorados_count' => 0,
                'copiados_count' => 0,
                'codigos_invalidos_count' => 0,
                'errores_count' => 0,
            ]);

        $filasLote = array_slice($filas, $offset, self::TAMANO_LOTE_IMPORTACION);

        $resultado = DB::transaction(fn () => $this->procesarLoteFilas(
            $filasLote, $offset, $mapeoPorIndice, $empresaId, $proveedorId, $modoDuplicados, $importacion,
        ));

        $importacion->update([
            'creados_count' => $importacion->creados_count + $resultado['creados'],
            'actualizados_count' => $importacion->actualizados_count + $resultado['actualizados'],
            'ignorados_count' => $importacion->ignorados_count + $resultado['ignorados'],
            'copiados_count' => $importacion->copiados_count + $resultado['copiados'],
            'codigos_invalidos_count' => $importacion->codigos_invalidos_count + $resultado['codigosInvalidos'],
            'errores_count' => $importacion->errores_count + count($resultado['errores']),
        ]);

        $terminado = ($offset + count($filasLote)) >= $total;

        if ($terminado) {
            Storage::disk('local')->delete($datosImportacion['ruta']);
            Cache::forget('import-productos:'.$request->validated('token'));

            MapeoImportacionProductos::updateOrCreate(
                ['empresa_id' => $empresaId],
                ['mapeo' => $this->construirMapeoParaGuardar($datosImportacion['encabezados'], $mapeoPorIndice)]
            );
        }

        return response()->json([
            'importacion_id' => $importacion->id,
            'offset_siguiente' => $offset + count($filasLote),
            'total' => $total,
            'terminado' => $terminado,
            'creados' => $resultado['creados'],
            'actualizados' => $resultado['actualizados'],
            'ignorados' => $resultado['ignorados'],
            'copiados' => $resultado['copiados'],
            'codigosInvalidos' => $resultado['codigosInvalidos'],
            'errores' => $resultado['errores'],
        ]);
    }

    /**
     * Núcleo compartido por confirmar() y procesarLote(): procesa un tramo
     * de filas (puede ser el archivo entero o un lote) contra el catálogo.
     *
     * Antes se hacía una consulta a la base por cada fila para ver si el
     * código ya existía — con miles de filas eso eran miles de consultas
     * individuales, el principal motivo de que un Excel grande se fuera a
     * timeout (504). Ahora se buscan TODOS los códigos de este tramo en una
     * sola consulta, y el resultado se guarda en memoria (array mutable,
     * no una colección inmutable) porque un código puede repetirse dentro
     * del mismo tramo: si la fila 5 crea el producto y la fila 50 repite el
     * mismo código, la fila 50 tiene que encontrar el que acaba de crear la
     * fila 5, no volver a crearlo (chocaría con la restricción única de
     * (empresa_id, codigo)).
     *
     * Los registros de ImportacionProductoCambio tampoco se insertan uno
     * por uno — se acumulan y se insertan todos juntos al final del tramo.
     *
     * @param  list<list<mixed>>  $filas
     * @param  int  $offsetGlobal  para que el número de fila en los mensajes
     *                              de error seaconsistente entre lotes (es
     *                              aproximado de todos modos: ver
     *                              LectorArchivoExcel sobre encabezados
     *                              repetidos descartados).
     * @param  array<int|string, string|null>  $mapeoPorIndice
     * @return array{creados: int, actualizados: int, ignorados: int, copiados: int, codigosInvalidos: int, errores: list<string>}
     */
    private function procesarLoteFilas(
        array $filas,
        int $offsetGlobal,
        array $mapeoPorIndice,
        int $empresaId,
        ?int $proveedorId,
        string $modoDuplicados,
        ImportacionProductos $importacion,
    ): array {
        $creados = 0;
        $actualizados = 0;
        $ignorados = 0;
        $copiados = 0;
        $codigosInvalidos = 0;
        $errores = [];
        $cambiosParaInsertar = [];

        $datosPorFila = [];
        $codigosDelTramo = [];

        foreach ($filas as $indiceLocal => $fila) {
            $datos = $this->extraerDatosFila($fila, $mapeoPorIndice);

            foreach (['precio', 'costo', 'stock'] as $campoNumerico) {
                if (array_key_exists($campoNumerico, $datos)) {
                    $datos[$campoNumerico] = $this->normalizador->normalizar($datos[$campoNumerico]);
                }
            }

            $datosPorFila[$indiceLocal] = $datos;

            $codigo = blank($datos['codigo'] ?? null) ? null : trim((string) $datos['codigo']);

            if ($codigo !== null && ! $this->esCodigoInvalido($codigo)) {
                $codigosDelTramo[] = $codigo;
            }
        }

        $productosExistentes = [];

        if ($codigosDelTramo !== []) {
            foreach (Producto::where('empresa_id', $empresaId)->whereIn('codigo', array_unique($codigosDelTramo))->get() as $productoExistente) {
                $productosExistentes[$productoExistente->codigo] = $productoExistente;
            }
        }

        foreach ($filas as $indiceLocal => $fila) {
            if (collect($fila)->every(fn ($valor) => trim((string) $valor) === '')) {
                continue;
            }

            $numeroFilaExcel = $offsetGlobal + $indiceLocal + 2;
            $datos = $datosPorFila[$indiceLocal];

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

            $producto = $codigo !== null ? ($productosExistentes[$codigo] ?? null) : null;

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

                    $cambiosParaInsertar[] = [
                        'importacion_id' => $importacion->id,
                        'producto_id' => $copia->id,
                        'fue_creado' => true,
                        'datos_anteriores' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    continue;
                }

                $datosAnteriores = $producto->only([
                    'nombre', 'codigo', 'precio', 'costo', 'stock', 'categoria', 'marca', 'unidad', 'proveedor_id',
                ]);

                $producto->update($atributos);
                $actualizados++;

                $cambiosParaInsertar[] = [
                    'importacion_id' => $importacion->id,
                    'producto_id' => $producto->id,
                    'fue_creado' => false,
                    'datos_anteriores' => json_encode($datosAnteriores),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                continue;
            }

            $nuevoProducto = Producto::create([...$atributos, 'empresa_id' => $empresaId, 'codigo' => $codigo]);
            $creados++;

            if ($codigo !== null) {
                // Para que una fila repetida más adelante en el mismo tramo
                // (mismo código) encuentre este producto recién creado en
                // vez de intentar crearlo de nuevo.
                $productosExistentes[$codigo] = $nuevoProducto;
            }

            $cambiosParaInsertar[] = [
                'importacion_id' => $importacion->id,
                'producto_id' => $nuevoProducto->id,
                'fue_creado' => true,
                'datos_anteriores' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($cambiosParaInsertar !== []) {
            ImportacionProductoCambio::insert($cambiosParaInsertar);
        }

        return compact('creados', 'actualizados', 'ignorados', 'copiados', 'codigosInvalidos', 'errores');
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
