<?php

namespace App\Http\Controllers;

use App\Models\AjustePrecioBusqueda;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AjustePrecioBusquedaController extends Controller
{
    public function index(Request $request): View
    {
        $ajustes = AjustePrecioBusqueda::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->with('usuario')
            ->orderByDesc('created_at')
            ->get();

        return view('productos.ajustes-precio.index', ['ajustes' => $ajustes]);
    }

    public function deshacer(Request $request, AjustePrecioBusqueda $ajuste): RedirectResponse
    {
        $this->autorizar($request, $ajuste);

        if ($ajuste->fueRevertido()) {
            return back()->withErrors(['ajuste' => 'Este ajuste ya fue revertido.']);
        }

        DB::transaction(function () use ($ajuste) {
            foreach ($ajuste->cambios as $cambio) {
                $producto = Producto::find($cambio->producto_id);

                // Si el precio ya cambió de nuevo desde que se aplicó este
                // ajuste (otro ajuste posterior, o una edición manual), no lo
                // pisamos a ciegas — solo se restaura si sigue en el valor
                // que este mismo ajuste dejó.
                if ($producto && (float) $producto->precio === (float) $cambio->precio_nuevo) {
                    $producto->update(['precio' => $cambio->precio_anterior]);
                }
            }

            $ajuste->update(['revertido_at' => now()]);
        });

        return redirect()->route('productos.ajustesPrecio.index')->with('status', 'Ajuste de precio revertido.');
    }

    private function autorizar(Request $request, AjustePrecioBusqueda $ajuste): void
    {
        if ($ajuste->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }
    }
}
