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

    private function autorizar(Request $request, ReposicionStock $reposicion): void
    {
        if ($reposicion->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }
    }
}
