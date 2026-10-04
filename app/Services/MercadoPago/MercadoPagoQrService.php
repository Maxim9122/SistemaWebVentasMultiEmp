<?php

namespace App\Services\MercadoPago;

use App\Models\Empresa;
use App\Models\IntentoPagoMercadopago;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Support\Str;

class MercadoPagoQrService
{
    public function __construct(private readonly MercadoPagoApiClient $cliente) {}

    /**
     * Punto de entrada único usado por Caja y Carrito para arrancar un
     * cobro con QR — valida que la empresa tenga el QR habilitado, que no
     * haya otro intento pendiente (el QR fijo solo puede tener UNA orden
     * pendiente a la vez, un segundo PUT pisaría al primero), crea el
     * registro del intento y crea la orden en Mercado Pago. Tira
     * \RuntimeException con un mensaje para mostrar si algo de esto no se
     * puede hacer — mismo criterio que ProcesadorDeCobro::cobrar().
     *
     * @param  array<string, mixed>  $montosJson  Todo lo validado del
     *     formulario de cobro, para que el webhook pueda reproducir el
     *     cobro completo sin tener la request HTTP disponible.
     */
    public function iniciarIntento(Empresa $empresa, Pedido $pedido, User $cajero, float $monto, array $montosJson): IntentoPagoMercadopago
    {
        $credencial = $empresa->credencialMercadoPago;

        if (! $empresa->pagoQrHabilitado() || ! $credencial) {
            throw new \RuntimeException('Esta empresa no tiene habilitado el cobro con QR de Mercado Pago.');
        }

        $yaHayUnoPendiente = IntentoPagoMercadopago::where('empresa_id', $empresa->id)
            ->where('estado', IntentoPagoMercadopago::ESTADO_PENDIENTE)
            ->where('expira_at', '>', now())
            ->exists();

        if ($yaHayUnoPendiente) {
            throw new \RuntimeException('Ya hay un cobro con QR en curso para esta empresa — esperá a que termine o venza antes de iniciar otro.');
        }

        $intento = IntentoPagoMercadopago::create([
            'empresa_id' => $empresa->id,
            'pedido_id' => $pedido->id,
            'user_id' => $cajero->id,
            'monto' => $monto,
            'external_reference' => (string) Str::uuid(),
            'estado' => IntentoPagoMercadopago::ESTADO_PENDIENTE,
            'expira_at' => now()->addMinutes(15),
            'montos_json' => $montosJson,
        ]);

        $this->crearOrdenQr($credencial->access_token, $credencial->mp_external_pos_id, $intento, $pedido);

        return $intento;
    }

    /**
     * Crea la orden (Orders API, config.qr.mode = "static") que asocia el
     * monto a la PRÓXIMA vez que se escanee el QR fijo — no genera ninguna
     * imagen nueva. La API de Orders no acepta notification_url por
     * pedido (a diferencia de Checkout API) — el webhook se configura UNA
     * sola vez a nivel aplicación (ver save_webhook / panel de Mercado
     * Pago). Por eso el intento se identifica después, en el webhook, por
     * external_reference (que sí viaja en el body de la notificación), no
     * por un query param armado a mano.
     */
    private function crearOrdenQr(string $accessToken, string $externalPosId, IntentoPagoMercadopago $intento, Pedido $pedido): void
    {
        $monto = number_format((float) $intento->monto, 2, '.', '');

        $this->cliente->crearOrdenQr($accessToken, [
            'type' => 'qr',
            'total_amount' => $monto,
            'description' => "Venta #{$pedido->id}",
            'external_reference' => $intento->external_reference,
            'expiration_time' => 'PT15M',
            'config' => [
                'qr' => [
                    'external_pos_id' => $externalPosId,
                    'mode' => 'static',
                ],
            ],
            'transactions' => [
                'payments' => [
                    ['amount' => $monto],
                ],
            ],
        ]);
    }
}
