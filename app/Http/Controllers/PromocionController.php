<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\PromocionItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromocionController extends Controller
{
    public function previsualizar(Request $request): View|RedirectResponse
    {
        $empresaId = $request->user()->empresa_id;

        $datos = $request->validate([
            'productos_ids' => ['required', 'array', 'min:2'],
            'productos_ids.*' => [
                'integer',
                Rule::exists('productos', 'id')->where('empresa_id', $empresaId)->where('es_promocion', false),
            ],
        ]);

        $productos = Producto::whereIn('id', $datos['productos_ids'])->orderBy('nombre')->get();

        return view('productos.promociones.crear', ['productos' => $productos]);
    }

    public function store(Request $request): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id;

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'precio_final' => ['required', 'numeric', 'min:0'],
            'productos_ids' => ['required', 'array', 'min:2'],
            'productos_ids.*' => [
                'integer',
                Rule::exists('productos', 'id')->where('empresa_id', $empresaId)->where('es_promocion', false),
            ],
            'cantidades' => ['required', 'array'],
            'cantidades.*' => ['integer', 'min:1'],
        ]);

        if (count($datos['productos_ids']) !== count($datos['cantidades'])) {
            return back()->withErrors(['productos_ids' => 'Los datos de la promoción quedaron inconsistentes, volvé a intentar.'])->withInput();
        }

        $productos = Producto::whereIn('id', $datos['productos_ids'])->get()->keyBy('id');
        $costoTotal = 0;

        foreach ($datos['productos_ids'] as $indice => $productoId) {
            $costoTotal += (float) $productos[$productoId]->precio * (int) $datos['cantidades'][$indice];
        }

        $promocion = DB::transaction(function () use ($empresaId, $datos, $costoTotal) {
            $promocion = Producto::create([
                'empresa_id' => $empresaId,
                'nombre' => $datos['nombre'],
                'precio' => $datos['precio_final'],
                'costo' => round($costoTotal, 2),
                'stock' => 0,
                'es_promocion' => true,
                'activo' => true,
            ]);

            foreach ($datos['productos_ids'] as $indice => $productoId) {
                PromocionItem::create([
                    'promocion_id' => $promocion->id,
                    'producto_id' => $productoId,
                    'cantidad' => (int) $datos['cantidades'][$indice],
                ]);
            }

            return $promocion;
        });

        return redirect()->route('productos.index')->with('status', "Promoción \"{$promocion->nombre}\" creada.");
    }

    public function edit(Request $request, Producto $promocion): View
    {
        $this->autorizar($request, $promocion);

        return view('productos.promociones.edit', [
            'promocion' => $promocion,
            'items' => $promocion->componentes()->with('producto')->get(),
        ]);
    }

    public function update(Request $request, Producto $promocion): RedirectResponse
    {
        $this->autorizar($request, $promocion);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'precio_final' => ['required', 'numeric', 'min:0'],
            'cantidades' => ['required', 'array'],
            'cantidades.*' => ['integer', 'min:1'],
            'eliminar' => ['nullable', 'array'],
            'eliminar.*' => ['integer'],
        ]);

        $idsAEliminar = collect($datos['eliminar'] ?? [])->map(fn ($id) => (int) $id);

        $idsValidosDelItem = $promocion->componentes()->pluck('id');
        $itemsRestantes = $idsValidosDelItem->diff($idsAEliminar)->count();

        if ($itemsRestantes < 2) {
            return back()->withErrors(['eliminar' => 'Una promoción necesita al menos 2 productos. No podés quitar tantos.'])->withInput();
        }

        DB::transaction(function () use ($promocion, $datos, $idsAEliminar) {
            if ($idsAEliminar->isNotEmpty()) {
                $promocion->componentes()->whereIn('id', $idsAEliminar)->delete();
            }

            foreach ($datos['cantidades'] as $itemId => $cantidad) {
                if ($idsAEliminar->contains((int) $itemId)) {
                    continue;
                }

                PromocionItem::where('id', $itemId)
                    ->where('promocion_id', $promocion->id)
                    ->update(['cantidad' => (int) $cantidad]);
            }

            $costoTotal = $promocion->componentes()->with('producto')->get()
                ->sum(fn (PromocionItem $item) => (float) $item->producto->precio * $item->cantidad);

            $promocion->update([
                'nombre' => $datos['nombre'],
                'precio' => $datos['precio_final'],
                'costo' => round($costoTotal, 2),
            ]);
        });

        return redirect()->route('productos.index')->with('status', 'Promoción actualizada.');
    }

    private function autorizar(Request $request, Producto $promocion): void
    {
        if ($promocion->empresa_id !== $request->user()->empresa_id || ! $promocion->es_promocion) {
            abort(404);
        }
    }
}
