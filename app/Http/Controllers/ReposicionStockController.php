<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ReposicionStock;
use App\Models\ReposicionStockItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReposicionStockController extends Controller
{
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

    public function store(Request $request): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id;

        $datos = $request->validate([
            'proveedor_id' => ['nullable', 'integer', Rule::exists('proveedores', 'id')->where('empresa_id', $empresaId)],
            'nota' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer', Rule::exists('productos', 'id')->where('empresa_id', $empresaId)],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ]);

        $reposicion = DB::transaction(function () use ($datos, $request, $empresaId) {
            $reposicion = ReposicionStock::create([
                'empresa_id' => $empresaId,
                'user_id' => $request->user()->id,
                'proveedor_id' => $datos['proveedor_id'] ?? null,
                'nota' => $datos['nota'] ?? null,
            ]);

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
