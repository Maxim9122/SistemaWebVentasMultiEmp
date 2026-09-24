<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Services\MercadoPago\OnboardingMercadoPagoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MercadoPagoController extends Controller
{
    public function conectar(Request $request, OnboardingMercadoPagoService $onboarding): RedirectResponse
    {
        $empresa = $request->user()->empresa;

        $url = $onboarding->urlAutorizacion($empresa, route('configuracion.mercadopago.callback'));

        return redirect()->away($url);
    }

    public function callback(Request $request, OnboardingMercadoPagoService $onboarding): RedirectResponse
    {
        // Mercado Pago puede devolver ?error=... si el dueño de la cuenta
        // cancela la autorización en vez de aceptarla.
        if ($request->filled('error')) {
            return redirect()->route('configuracion.edit')
                ->withErrors(['mercadopago' => 'No se completó la conexión con Mercado Pago.']);
        }

        $empresaId = $onboarding->decodificarState((string) $request->input('state'));

        if (! $empresaId || $empresaId !== $request->user()->empresa_id) {
            return redirect()->route('configuracion.edit')
                ->withErrors(['mercadopago' => 'El link de conexión venció o no es válido. Probá de nuevo.']);
        }

        try {
            $onboarding->completarAutorizacion(
                Empresa::findOrFail($empresaId),
                (string) $request->input('code'),
                route('configuracion.mercadopago.callback'),
            );
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('configuracion.edit')
                ->withErrors(['mercadopago' => 'No se pudo completar la conexión con Mercado Pago. Probá de nuevo en unos minutos.']);
        }

        return redirect()->route('configuracion.edit')->with('status', 'Cuenta de Mercado Pago conectada.');
    }
}
