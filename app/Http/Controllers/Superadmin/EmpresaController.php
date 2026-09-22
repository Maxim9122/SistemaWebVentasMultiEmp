<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmpresaController extends Controller
{
    public function index(Request $request): View
    {
        $estado = $request->query('estado', 'pendiente');

        $empresas = Empresa::query()
            ->when($estado !== 'todas', fn ($query) => $query->where('estado', $estado))
            ->latest()
            ->get();

        // Para el cartel de "CUIT repetido" en la lista, sin un query por
        // fila: agrupa TODOS los cuits de la tabla (no solo los de esta
        // página filtrada) y se queda con los que aparecen más de una vez.
        $cuitsDuplicados = Empresa::query()
            ->select('cuit')
            ->groupBy('cuit')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('cuit');

        return view('superadmin.empresas.index', [
            'empresas' => $empresas,
            'estado' => $estado,
            'cuitsDuplicados' => $cuitsDuplicados,
        ]);
    }

    public function show(Empresa $empresa): View
    {
        $empresa->load('users');

        return view('superadmin.empresas.show', [
            'empresa' => $empresa,
            'empresasConMismoCuit' => $empresa->empresasConMismoCuit(),
        ]);
    }

    public function aprobar(Empresa $empresa): RedirectResponse
    {
        $empresa->update([
            'estado' => 'activa',
            'motivo_rechazo' => null,
            'motivo_suspension' => null,
        ]);

        return back()->with('status', 'Empresa aprobada.');
    }

    public function rechazar(Request $request, Empresa $empresa): RedirectResponse
    {
        $request->validate(['motivo' => ['nullable', 'string', 'max:1000']]);

        $empresa->update([
            'estado' => 'rechazada',
            'motivo_rechazo' => $request->input('motivo'),
        ]);

        return back()->with('status', 'Empresa rechazada.');
    }

    public function suspender(Request $request, Empresa $empresa): RedirectResponse
    {
        $request->validate(['motivo' => ['nullable', 'string', 'max:1000']]);

        $empresa->update([
            'estado' => 'suspendida',
            'motivo_suspension' => $request->input('motivo'),
        ]);

        return back()->with('status', 'Empresa suspendida.');
    }

    public function reactivar(Empresa $empresa): RedirectResponse
    {
        $empresa->update([
            'estado' => 'activa',
            'motivo_rechazo' => null,
            'motivo_suspension' => null,
        ]);

        return back()->with('status', 'Empresa reactivada.');
    }
}
