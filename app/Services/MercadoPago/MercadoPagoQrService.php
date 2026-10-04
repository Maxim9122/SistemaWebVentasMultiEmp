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

        $ordenId = $this->crearOrdenQr($credencial->access_token, $credencial->mp_external_pos_id, $intento, $pedido);

        // Se guarda ya en la creación (no solo al aprobarse) para poder
        // cancelar la orden en Mercado Pago si el cajero cancela el cobro
        // mientras espera — sin esto no hay forma de identificar qué orden
        // cancelar del lado de Mercado Pago.
        $intento->update(['mp_merchant_order_id' => $ordenId]);

        return $intento;
    }

    /**
     * Cancela un intento todavía pendiente — tanto acá como en Mercado
     * Pago (para que el QR fijo deje de tener ese monto asociado). El
     * pedido NO se toca: sigue con sus items tal cual, para que el cajero
     * pueda reintentar con otro medio de pago sin perder nada.
     */
    public function cancelarIntento(IntentoPagoMercadopago $intento): void
    {
        if ($intento->estado !== IntentoPagoMercadopago::ESTADO_PENDIENTE) {
            throw new \RuntimeException('Este cobro ya no está pendiente — es posible que ya se haya aprobado o vencido.');
        }

        $credencial = $intento->empresa->credencialMercadoPago;

        if ($credencial && $intento->mp_merchant_order_id) {
            try {
                $this->cliente->cancelarOrden($credencial->access_token, $intento->mp_merchant_order_id);
            } catch (\Throwable $e) {
                // Lo más probable es que el cliente ya haya pagado justo
                // cuando el cajero apretó cancelar (Mercado Pago no deja
                // cancelar una orden ya procesada) — no se marca cancelado
                // acá: se deja que el próximo polling/webhook resuelva el
                // estado real en vez de pisarlo con una cancelación que no
                // ocurrió de verdad del lado de Mercado Pago.
                throw new \RuntimeException('No se pudo cancelar — puede que el cliente ya haya pagado. Esperá unos segundos y fijate si se acreditó.');
            }
        }

        $intento->update(['estado' => IntentoPagoMercadopago::ESTADO_CANCELADO]);
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
    private function crearOrdenQr(string $accessToken, string $externalPosId, IntentoPagoMercadopago $intento, Pedido $pedido): string
    {
        $monto = number_format((float) $intento->monto, 2, '.', '');

        $orden = $this->cliente->crearOrdenQr($accessToken, [
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

        return (string) ($orden['id'] ?? '');
    }
}
