<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubirFotoReposicionRequest;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\ReposicionStock;
use App\Models\ReposicionStockItem;
use App\Services\AjustePrecioService;
use App\Services\ClaudeVision\ClaudeVisionApiClient;
use App\Services\ClaudeVision\Exceptions\ExtraccionFotoException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReposicionStockController extends Controller
{
    public function __construct(private readonly ClaudeVisionApiClient $claudeVision) {}

    public function subirFoto(Request $request): View
    {
        abort_if(! $this->claudeVision->configurada(), 404);

        $proveedores = Proveedor::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('productos.reposiciones.foto-subir', ['proveedores' => $proveedores]);
    }

    public function extraerFoto(SubirFotoReposicionRequest $request): View
    {
        abort_if(! $this->claudeVision->configurada(), 404);

        $empresaId = $request->user()->empresa_id;
        $foto = $request->file('foto');
        $rutaTemporal = $foto->getRealPath();

        $this->redimensionarSiHaceFalta($rutaTemporal);

        try {
            $items = $this->claudeVision->extraerReposicionDeFoto(
                file_get_contents($rutaTemporal),
                $foto->getMimeType(),
            );
        } catch (ExtraccionFotoException $e) {
            return back()->withErrors(['foto' => $e->getMessage()])->withInput();
        }

        $filas = collect($items)->map(function (array $item) use ($empresaId) {
            $nombre = trim((string) ($item['nombre'] ?? ''));
            $codigo = trim((string) ($item['codigo'] ?? ''));
            $cantidad = $this->limpiarCantidad($item['cantidad'] ?? null);

            $sugerido = $this->sugerirProducto($empresaId, $nombre, $codigo);

            return [
                'nombre_leido' => $nombre,
                'codigo_leido' => $codigo,
                'cantidad' => $cantidad,
                'producto_id' => $sugerido?->id,
                'producto_nombre' => $sugerido?->nombre,
                'producto_codigo' => $sugerido?->codigo,
                'producto_stock' => $sugerido?->stock,
                'producto_precio' => $sugerido?->precio,
            ];
        })->values()->all();

        return view('productos.reposiciones.foto-revisar', [
            'filas' => $filas,
            'proveedorId' => $request->validated('proveedor_id'),
            'urlBuscar' => route('productos.buscarParaReponer'),
        ]);
    }

    /**
     * Solo auto-sugiere con una coincidencia exacta (código exacto, o
     * nombre exacto sin importar mayúsculas/minúsculas) — a propósito NUNCA
     * busca por "parecido" (LIKE) para esto: sumarle stock al producto
     * equivocado en silencio es mucho peor que dejarle la fila sin sugerir
     * para que el usuario la busque a mano.
     */
    private function sugerirProducto(int $empresaId, string $nombre, string $codigo): ?Producto
    {
        if ($codigo !== '') {
            $producto = Producto::where('empresa_id', $empresaId)->where('codigo', $codigo)->first();

            if ($producto) {
                return $producto;
            }
        }

        if ($nombre !== '') {
            return Producto::where('empresa_id', $empresaId)->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])->first();
        }

        return null;
    }

    private function limpiarCantidad(mixed $valor): ?int
    {
        if ($valor === null) {
            return null;
        }

        $valor = preg_replace('/[^\d]/', '', (string) $valor) ?? '';

        return $valor === '' ? null : (int) $valor;
    }

    /**
     * 1568px de lado más largo es lo que Anthropic documenta como el punto
     * óptimo costo/calidad para vision — ver mismo helper en
     * ProductoImportController/ClienteImportController.
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

    public function index(Request $request): View
    {
        $reposiciones = ReposicionStock::where('empresa_id', $request->user()->empresa_id)
            ->with(['user', 'proveedor'])
            ->withCount('items')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('productos.reposiciones.index', ['reposiciones' => $reposiciones]);
    }

    public function show(Request $request, ReposicionStock $reposicion): View
    {
        $this->autorizar($request, $reposicion);

        return view('productos.reposiciones.show', [
            'reposicion' => $reposicion->load(['items', 'user', 'proveedor']),
        ]);
    }

    /**
     * `porcentaje`/`ajustar_precio_ids` son opcionales y los usa solo la
     * pantalla de "reponer stock desde una foto" (ver extraerFoto()): el
     * modal manual de reponer stock nunca los manda, así que su
     * comportamiento queda exactamente igual que antes.
     */
    public function store(Request $request, AjustePrecioService $ajustador): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id;

        $datos = $request->validate([
            'proveedor_id' => ['nullable', 'integer', Rule::exists('proveedores', 'id')->where('empresa_id', $empresaId)],
            'nota' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', Rule::exists('productos', 'id')->where('empresa_id', $empresaId)],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'porcentaje' => ['nullable', 'numeric', 'between:-100,1000'],
            'ajustar_precio_ids' => ['nullable', 'array'],
            'ajustar_precio_ids.*' => ['integer'],
        ]);

        $reposicion = DB::transaction(function () use ($datos, $request, $empresaId, $ajustador) {
            $reposicion = ReposicionStock::create([
                'empresa_id' => $empresaId,
                'user_id' => $request->user()->id,
                'proveedor_id' => $datos['proveedor_id'] ?? null,
                'nota' => $datos['nota'] ?? null,
            ]);

            $productosParaAjustar = [];

            foreach ($datos['items'] as $item) {
                // lockForUpdate: si dos reposiciones del mismo producto se
                // guardan casi en simultáneo, que se sumen las dos sobre el
                // stock real en ese momento, no que una pise a la otra.
                $producto = Producto::where('id', $item['producto_id'])
                    ->where('empresa_id', $empresaId)
                    ->lockForUpdate()
                    ->first();

                if (! $producto) {
                    continue;
                }

                $cantidadAnterior = $producto->stock;
                $cantidadNueva = $cantidadAnterior + $item['cantidad'];

                $producto->update(['stock' => $cantidadNueva]);

                ReposicionStockItem::create([
                    'reposicion_id' => $reposicion->id,
                    'producto_id' => $producto->id,
                    'nombre_producto' => $producto->nombre,
                    'cantidad_anterior' => $cantidadAnterior,
                    'cantidad_agregada' => $item['cantidad'],
                    'cantidad_nueva' => $cantidadNueva,
                ]);

                $productosParaAjustar[$producto->id] = $producto;
            }

            if (filled($datos['porcentaje'] ?? null)) {
                $idsSeleccionados = $datos['ajustar_precio_ids'] ?? null;

                $productos = filled($idsSeleccionados)
                    ? collect($productosParaAjustar)->whereIn('id', $idsSeleccionados)->values()
                    : collect($productosParaAjustar)->values();

                if ($productos->isNotEmpty()) {
                    $ajustador->aplicar(
                        $productos,
                        (float) $datos['porcentaje'],
                        $empresaId,
                        $request->user()->id,
                        'Reposición de stock desde una foto',
                    );
                }
            }

            return $reposicion;
        });

        return redirect()->route('productos.index')
            ->with('status', 'Stock repuesto: '.$reposicion->items()->count().' producto(s) actualizado(s).');
    }

    public function deshacer(Request $request, ReposicionStock $reposicion): RedirectResponse
    {
        $this->autorizar($request, $reposicion);

        if ($reposicion->fueRevertida()) {
            return back()->withErrors(['reposicion' => 'Esta reposición ya fue revertida.']);
        }

        DB::transaction(function () use ($reposicion) {
            foreach ($reposicion->items as $item) {
                if ($item->producto_id) {
                    // Resta lo agregado, no vuelve a "cantidad_anterior": si
                    // hubo ventas u otra reposición después de esta, pisar
                    // el stock actual con el valor viejo borraría esos
                    // movimientos posteriores. Restar lo agregado deshace
                    // solo lo que este lote sumó, sea cual sea el stock hoy.
                    Producto::where('id', $item->producto_id)->decrement('stock', $item->cantidad_agregada);
                }
            }

            $reposicion->update(['revertida_at' => now()]);
        });

        return redirect()->route('productos.reposiciones.index')->with('status', 'Reposición revertida.');
    }

    /**
     * Botón "Editar reposición" — corrige un lote ya guardado línea por
     * línea. Cada línea puede venir con una cantidad
     * nueva (se ajusta el stock por la diferencia, no se vuelve a sumar
     * todo de nuevo) o directamente no venir más en `items` (se borra esa
     * línea y se le resta al stock lo que esa línea había sumado). Si al
     * final no queda ninguna línea, es exactamente "deshacer todo" — queda
     * el lote marcado como revertido en vez de borrarlo, para no perder el
     * rastro de que existió.
     */
    public function update(Request $request, ReposicionStock $reposicion): RedirectResponse
    {
        $this->autorizar($request, $reposicion);
        $empresaId = $request->user()->empresa_id;

        $datos = $request->validate([
            'proveedor_id' => ['nullable', 'integer', Rule::exists('proveedores', 'id')->where('empresa_id', $empresaId)],
            'nota' => ['nullable', 'string', 'max:1000'],
            'items' => ['array'],
            'items.*.producto_id' => ['required', 'integer', Rule::exists('productos', 'id')->where('empresa_id', $empresaId)],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($datos, $reposicion, $empresaId) {
            $itemsActuales = $reposicion->items()->get()->keyBy('producto_id');
            $idsEnPayload = collect($datos['items'] ?? [])->pluck('producto_id')->map(fn ($id) => (int) $id);

            // Líneas que ya no vienen en el payload: se sacaron del modal ->
            // restar lo que habían sumado y borrar la fila.
            foreach ($itemsActuales as $productoId => $item) {
                if (! $idsEnPayload->contains($productoId) && $item->producto_id) {
                    Producto::where('id', $item->producto_id)->increment('stock', -$item->cantidad_agregada);
                    $item->delete();
                }
            }

            foreach ($datos['items'] ?? [] as $itemPayload) {
                $productoId = (int) $itemPayload['producto_id'];
                $cantidadNueva = (int) $itemPayload['cantidad'];
                $existente = $itemsActuales->get($productoId);

                $producto = Producto::where('id', $productoId)->where('empresa_id', $empresaId)->lockForUpdate()->first();

                if (! $producto) {
                    continue;
                }

                if ($existente) {
                    $delta = $cantidadNueva - $existente->cantidad_agregada;

                    if ($delta !== 0) {
                        $producto->increment('stock', $delta);
                        $existente->update([
                            'cantidad_agregada' => $cantidadNueva,
                            'cantidad_nueva' => $existente->cantidad_anterior + $cantidadNueva,
                        ]);
                    }
                } else {
                    // Producto agregado recién ahora, al editar.
                    $cantidadAnterior = $producto->stock;
                    $cantidadNuevaStock = $cantidadAnterior + $cantidadNueva;
                    $producto->update(['stock' => $cantidadNuevaStock]);

                    ReposicionStockItem::create([
                        'reposicion_id' => $reposicion->id,
                        'producto_id' => $producto->id,
                        'nombre_producto' => $producto->nombre,
                        'cantidad_anterior' => $cantidadAnterior,
                        'cantidad_agregada' => $cantidadNueva,
                        'cantidad_nueva' => $cantidadNuevaStock,
                    ]);
                }
            }

            $reposicion->update([
                'proveedor_id' => $datos['proveedor_id'] ?? null,
                'nota' => $datos['nota'] ?? null,
                'revertida_at' => $reposicion->items()->count() === 0 ? now() : null,
            ]);
        });

        return redirect()->route('productos.reposiciones.show', $reposicion)->with('status', 'Reposición actualizada.');
    }

    private function autorizar(Request $request, ReposicionStock $reposicion): void
    {
        if ($reposicion->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }
    }
}
