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
        // whereNotNull a propósito: el CUIT ahora es opcional, y MySQL
        // agrupa todos los NULL juntos — sin este filtro, dos empresas
        // sin CUIT cargado se marcarían como "duplicadas" entre sí.
        $cuitsDuplicados = Empresa::query()
            ->whereNotNull('cuit')
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
        if ($error = $this->bloqueadaPorCuitDuplicado($empresa)) {
            return back()->withErrors(['aprobar' => $error]);
        }

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
        if ($error = $this->bloqueadaPorCuitDuplicado($empresa)) {
            return back()->withErrors(['aprobar' => $error]);
        }

        $empresa->update([
            'estado' => 'activa',
            'motivo_rechazo' => null,
            'motivo_suspension' => null,
        ]);

        return back()->with('status', 'Empresa reactivada.');
    }

    /**
     * Nunca deja que dos empresas con el mismo CUIT queden `activa` al mismo
     * tiempo — aunque el alta duplicada en sí ya no se bloquea (el
     * superadmin la ve y decide con criterio, ver `index()`), activar dos a
     * la vez sí se bloquea siempre: la API de facturación identifica una
     * empresa por CUIT en varios puntos (ver
     * `FacturacionOnboardingWebhookController::localizarEmpresa()`), y dos
     * activas con el mismo CUIT reintroducirían la ambigüedad que ese
     * webhook fue diseñado para evitar.
     */
    private function bloqueadaPorCuitDuplicado(Empresa $empresa): ?string
    {
        $otraActiva = $empresa->empresasConMismoCuit()->firstWhere('estado', 'activa');

        if (! $otraActiva) {
            return null;
        }

        return "No se puede activar: ya hay otra empresa activa (\"{$otraActiva->razon_social}\") con el mismo CUIT {$empresa->cuit}. Suspendé o rechazá esa otra empresa primero, o confirmá con el dueño que se trata de la misma empresa antes de continuar.";
    }
}
