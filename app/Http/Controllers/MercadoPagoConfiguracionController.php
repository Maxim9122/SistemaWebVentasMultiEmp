<?php

namespace App\Http\Controllers;

use App\Services\MercadoPago\MercadoPagoCuentaService;
use App\Services\MercadoPago\MercadoPagoOAuthService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MercadoPagoConfiguracionController extends Controller
{
    public function conectar(Request $request, MercadoPagoOAuthService $oauth): RedirectResponse
    {
        return redirect()->away(
            $oauth->urlDeAutorizacion($request->user()->empresa, $request->boolean('sandbox'))
        );
    }

    public function callback(Request $request, MercadoPagoOAuthService $oauth): RedirectResponse
    {
        $datos = $oauth->datosDesdeState((string) $request->query('state'));

        if (! $datos || $datos['empresa_id'] !== $request->user()->empresa_id) {
            return redirect()->route('configuracion.edit')
                ->withErrors(['mercadopago' => 'El enlace de conexión con Mercado Pago no es válido o venció. Intentá conectar de nuevo.']);
        }

        $code = (string) $request->query('code');

        if ($code === '') {
            return redirect()->route('configuracion.edit')
                ->withErrors(['mercadopago' => 'Mercado Pago no autorizó la conexión. Intentá de nuevo.']);
        }

        $oauth->manejarCallback($request->user()->empresa, $code, $datos['sandbox']);

        return redirect()->route('configuracion.edit')->with('status', 'Cuenta de Mercado Pago conectada.');
    }

    public function configurarPos(Request $request, MercadoPagoCuentaService $cuenta): RedirectResponse
    {
        $empresa = $request->user()->empresa;
        $credencial = $empresa->credencialMercadoPago;

        if (! $credencial || ! $credencial->estaActiva()) {
            return back()->withErrors(['mercadopago' => 'Primero conectá la cuenta de Mercado Pago.']);
        }

        // location es obligatorio para Mercado Pago (afecta cálculos de
        // impuestos de su lado) — no se autogenera ni se adivina.
        $datos = $request->validate([
            'calle' => ['required', 'string', 'max:255'],
            'numero' => ['required', 'string', 'max:20'],
            'ciudad' => ['required', 'string', 'max:255'],
            'provincia' => ['required', 'string', 'max:255'],
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            $cuenta->crearStoreYPos($credencial, $empresa->razon_social, [
                'street_number' => $datos['numero'],
                'street_name' => $datos['calle'],
                'city_name' => $datos['ciudad'],
                'state_name' => $datos['provincia'],
                'latitude' => (float) $datos['latitud'],
                'longitude' => (float) $datos['longitud'],
                'reference' => $empresa->razon_social,
            ]);
        } catch (RequestException $e) {
            // Mercado Pago valida ciudad/provincia contra una lista cerrada
            // de localidades propia (no es texto libre) — si no coincide
            // exacto, devuelve 400 con el detalle. Se lo mostramos tal cual
            // en vez de la pantalla de error genérica de Laravel.
            $detalle = $e->response->json('message') ?? $e->response->json('causes.0.description') ?? $e->response->body();

            return back()->withErrors(['mercadopago' => "Mercado Pago rechazó esos datos: {$detalle}"])->withInput();
        }

        return back()->with('status', 'Caja configurada — ya podés imprimir el QR.');
    }
}
