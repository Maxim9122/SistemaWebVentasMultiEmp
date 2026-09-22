<?php

namespace App\Http\Controllers;

use App\Models\MotivoEgreso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MotivoEgresoController extends Controller
{
    public function index(Request $request): View
    {
        $empresa = $request->user()->empresa;
        $empresa->asegurarMotivosEgresoPorDefecto();

        return view('egresos.motivos', [
            'motivos' => $empresa->motivosEgreso()->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id;

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('motivos_egreso', 'nombre')->where('empresa_id', $empresaId)],
        ]);

        if ($request->boolean('requiere_producto') && $request->boolean('solo_proveedores')) {
            return back()->withErrors(['solo_proveedores' => 'Un motivo no puede ser "solo proveedores" y "requiere producto" a la vez.'])->withInput();
        }

        MotivoEgreso::create([
            ...$datos,
            'empresa_id' => $empresaId,
            'activo' => true,
            'requiere_producto' => $request->boolean('requiere_producto'),
            'solo_proveedores' => $request->boolean('solo_proveedores'),
        ]);

        return redirect()->route('egresos.motivos.index')->with('status', 'Motivo agregado.');
    }

    public function alternarRequiereProducto(Request $request, MotivoEgreso $motivo): RedirectResponse
    {
        $this->autorizar($request, $motivo);

        $nuevoValor = ! $motivo->requiere_producto;

        $motivo->update([
            'requiere_producto' => $nuevoValor,
            'solo_proveedores' => $nuevoValor ? false : $motivo->solo_proveedores,
        ]);

        return back()->with('status', 'Motivo actualizado.');
    }

    public function alternarSoloProveedores(Request $request, MotivoEgreso $motivo): RedirectResponse
    {
        $this->autorizar($request, $motivo);

        $nuevoValor = ! $motivo->solo_proveedores;

        $motivo->update([
            'solo_proveedores' => $nuevoValor,
            'requiere_producto' => $nuevoValor ? false : $motivo->requiere_producto,
        ]);

        return back()->with('status', 'Motivo actualizado.');
    }

    public function activar(Request $request, MotivoEgreso $motivo): RedirectResponse
    {
        $this->autorizar($request, $motivo);

        $motivo->update(['activo' => true]);

        return back()->with('status', 'Motivo activado.');
    }

    public function desactivar(Request $request, MotivoEgreso $motivo): RedirectResponse
    {
        $this->autorizar($request, $motivo);

        $motivo->update(['activo' => false]);

        return back()->with('status', 'Motivo desactivado.');
    }

    private function autorizar(Request $request, MotivoEgreso $motivo): void
    {
        if ($motivo->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }
    }
}
