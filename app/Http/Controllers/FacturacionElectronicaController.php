<?php

namespace App\Http\Controllers;

use App\Services\Facturacion\Exceptions\CuitYaRegistradoException;
use App\Services\Facturacion\OnboardingFacturacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FacturacionElectronicaController extends Controller
{
    public function iniciar(Request $request, OnboardingFacturacionService $onboarding): RedirectResponse
    {
        $empresa = $request->user()->empresa;

        if (! $empresa->puedeFacturar()) {
            abort(403);
        }

        // Re-disparar este flujo con una empresa ya activa es la forma soportada de
        // renovar/reemplazar el certificado (cambio de dueño, vencimiento, compromiso
        // de clave, etc.) — la API de facturación revoca la API Key anterior recién
        // cuando se completa el onboarding nuevo, así que no corta el servicio de
        // entrada. No hay que bloquear este caso.
        $datos = $request->validate([
            'ambiente' => ['required', Rule::in(['homologacion', 'produccion'])],
        ]);

        try {
            $onboardingUrl = $onboarding->iniciar($empresa, $datos['ambiente']);
        } catch (CuitYaRegistradoException $e) {
            return back()->withErrors(['facturacion' => $e->getMessage()]);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'facturacion' => 'No se pudo iniciar la configuración de facturación electrónica. Probá de nuevo en unos minutos.',
            ]);
        }

        return redirect()->away($onboardingUrl);
    }
}
