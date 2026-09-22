<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ImportacionClientes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ImportacionClienteController extends Controller
{
    public function index(Request $request): View
    {
        $importaciones = ImportacionClientes::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->with('usuario')
            ->orderByDesc('created_at')
            ->get();

        return view('clientes.importaciones.index', ['importaciones' => $importaciones]);
    }

    public function deshacer(Request $request, ImportacionClientes $importacion): RedirectResponse
    {
        $this->autorizar($request, $importacion);

        if ($importacion->fueRevertida()) {
            return back()->withErrors(['importacion' => 'Esta importación ya fue revertida.']);
        }

        DB::transaction(function () use ($importacion) {
            foreach ($importacion->cambios as $cambio) {
                if ($cambio->fue_creado) {
                    Cliente::where('id', $cambio->cliente_id)->delete();

                    continue;
                }

                $cliente = Cliente::find($cambio->cliente_id);

                if ($cliente && $cambio->datos_anteriores) {
                    $cliente->update($cambio->datos_anteriores);
                }
            }

            $importacion->update(['revertida_at' => now()]);
        });

        return redirect()->route('clientes.importaciones.index')->with('status', 'Importación revertida.');
    }

    private function autorizar(Request $request, ImportacionClientes $importacion): void
    {
        if ($importacion->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }
    }
}
