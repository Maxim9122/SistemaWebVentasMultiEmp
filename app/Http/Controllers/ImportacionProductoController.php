<?php

namespace App\Http\Controllers;

use App\Models\ImportacionProductos;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ImportacionProductoController extends Controller
{
    public function index(Request $request): View
    {
        $importaciones = ImportacionProductos::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->with(['usuario', 'proveedor'])
            ->orderByDesc('created_at')
            ->get();

        return view('productos.importaciones.index', ['importaciones' => $importaciones]);
    }

    public function deshacer(Request $request, ImportacionProductos $importacion): RedirectResponse
    {
        $this->autorizar($request, $importacion);

        if ($importacion->fueRevertida()) {
            return back()->withErrors(['importacion' => 'Esta importación ya fue revertida.']);
        }

        DB::transaction(function () use ($importacion) {
            foreach ($importacion->cambios as $cambio) {
                if ($cambio->fue_creado) {
                    Producto::where('id', $cambio->producto_id)->delete();

                    continue;
                }

                $producto = Producto::find($cambio->producto_id);

                if ($producto && $cambio->datos_anteriores) {
                    $producto->update($cambio->datos_anteriores);
                }
            }

            $importacion->update(['revertida_at' => now()]);
        });

        return redirect()->route('productos.importaciones.index')->with('status', 'Importación revertida.');
    }

    private function autorizar(Request $request, ImportacionProductos $importacion): void
    {
        if ($importacion->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }
    }
}
