<?php

namespace App\Services\MercadoPago;

use App\Models\IntentoPagoMercadopago;
use App\Services\Facturacion\EmisionComprobanteService;
use App\Services\ProcesadorDeCobro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Verificación de firma + procesamiento de la notificación de pago QR de
 * Mercado Pago. El secreto es uno solo por Application registrada en
 * Mercado Pago (a nivel plataforma, configurado una sola vez en el panel o
 * vía save_webhook) — mismo criterio que el webhook de *onboarding* de
 * AFIP — así que la firma se verifica ANTES de mirar nada del payload.
 *
 * Confirmado contra la documentación real de Mercado Pago (no supuesto):
 * la API de Orders (usada por el QR fijo) no acepta un notification_url
 * por pedido — el webhook llega siempre a la MISMA URL configurada a nivel
 * aplicación, con topic "order". Por eso el intento se identifica acá por
 * `external_reference` (que sí viaja en el body de la notificación), no
 * por un query param armado a mano en la creación de la orden.
 */
class MercadoPagoWebhookService
{
    public function __construct(
        private readonly ProcesadorDeCobro $procesador,
        private readonly EmisionComprobanteService $emisor,
    ) {}

    /**
     * Mercado Pago firma sobre un "manifest" armado a mano, no sobre el
     * body crudo como AFIP: `id:<data.id>;request-id:<x-request-id>;ts:<ts>;`
     * (orden fijo, termina en ";", y cada parte se omite del manifest si no
     * vino en la notificación). El header x-signature viene como
     * "ts=1700000000,v1=abc123...".
     *
     * OJO con un detalle real de PHP/Laravel: Mercado Pago manda el query
     * param como "data.id", pero PHP convierte los puntos de los nombres de
     * parámetros de query string a guión bajo antes de que lleguen acá —
     * por eso se lee como "data_id", no "data.id" (si se lee mal, la firma
     * da mal SIEMPRE y ningún pago se confirma nunca — no es un detalle
     * menor).
     */
    public function firmaValida(Request $request, string $secret): bool
    {
        $firma = (string) $request->header('x-signature');
        $requestId = (string) $request->header('x-request-id');
        $dataId = mb_strtolower((string) $request->query('data_id', ''));

        if ($firma === '') {
            return false;
        }

        $partes = [];

        foreach (explode(',', $firma) as $parte) {
            [$clave, $valor] = array_pad(explode('=', trim($parte), 2), 2, null);

            if ($clave !== null && $valor !== null) {
                $partes[trim($clave)] = trim($valor);
            }
        }

        $ts = $partes['ts'] ?? null;
        $v1Recibido = $partes['v1'] ?? null;

        if ($ts === null || $v1Recibido === null) {
            return false;
        }

        $segmentos = [];

        if ($dataId !== '') {
            $segmentos[] = "id:{$dataId}";
        }

        if ($requestId !== '') {
            $segmentos[] = "request-id:{$requestId}";
        }

        $segmentos[] = "ts:{$ts}";

        $manifest = implode(';', $segmentos).';';
        $v1Esperado = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($v1Esperado, $v1Recibido);
    }

    public function procesarNotificacion(array $payload): void
    {
        // Solo nos interesan las notificaciones de órdenes de QR Code — el
        // mismo webhook de aplicación puede traer otros topics (pagos de
        // Point, reclamos, etc.) si la cuenta los tiene habilitados.
        if (($payload['type'] ?? null) !== 'order' || ($payload['data']['type'] ?? null) !== 'qr') {
            return;
        }

        $data = $payload['data'] ?? [];
        $externalReference = (string) ($data['external_reference'] ?? '');

        if ($externalReference === '') {
            Log::warning('Webhook de Mercado Pago: notificación de orden QR sin external_reference.', ['payload' => $payload]);

            return;
        }

        $intento = IntentoPagoMercadopago::where('external_reference', $externalReference)->first();

        if (! $intento) {
            Log::warning('Webhook de Mercado Pago: no se encontró el intento.', ['external_reference' => $externalReference]);

            return;
        }

        if (in_array($intento->estado, [IntentoPagoMercadopago::ESTADO_APROBADO, IntentoPagoMercadopago::ESTADO_CANCELADO], true)) {
            // Reintento de un webhook ya procesado, o de un intento que se
            // canceló a propósito — Mercado Pago reintenta notificaciones
            // que no devuelven 2xx a tiempo, esto es esperable, no es un
            // error.
            return;
        }

        // OJO: "expirado" NO se descarta acá — es una marca optimista
        // nuestra (nuestro propio reloj de 15 minutos, no el de Mercado
        // Pago) que la pantalla de espera pone sola si el webhook tarda en
        // llegar. Si el pago en realidad SÍ se acreditó (confirmado abajo
        // contra el monto real), hay que cobrar la venta igual — lo
        // contrario sería perder una venta ya cobrada de verdad por un
        // problema de timing nuestro, no de Mercado Pago.

        // "order.processed" + status "processed" es la confirmación real de
        // pago acreditado para este producto — el resto de las acciones
        // (order.canceled, order.refunded) no confirman un cobro nuevo.
        $accion = (string) ($payload['action'] ?? '');
        $estadoOrden = (string) ($data['status'] ?? '');

        if ($accion !== 'order.processed' || $estadoOrden !== 'processed') {
            return;
        }

        if (round((float) ($data['total_paid_amount'] ?? $data['total_amount'] ?? 0), 2) !== round((float) $intento->monto, 2)) {
            Log::warning('Webhook de Mercado Pago: el monto pagado no coincide con el intento.', [
                'intento_id' => $intento->id,
                'esperado' => (float) $intento->monto,
                'recibido' => $data['total_paid_amount'] ?? $data['total_amount'] ?? null,
            ]);

            return;
        }

        $this->confirmarCobro($intento, (string) ($data['id'] ?? ''));
    }

    private function confirmarCobro(IntentoPagoMercadopago $intento, string $ordenId): void
    {
        $pedido = $intento->pedido;
        $cajero = $intento->cajero;
        $montos = $intento->montos_json ?? [];

        if (! $pedido || ! $cajero) {
            Log::error('Webhook de Mercado Pago: intento sin pedido/cajero asociado.', ['intento_id' => $intento->id]);

            return;
        }

        // Si el pedido YA está cobrado (reintento de este mismo webhook, o
        // el cajero lo cobró por otro medio mientras tanto), es el caso
        // idempotente de verdad — nada que hacer, no es un error.
        if ($pedido->fresh()->esCobrado()) {
            Log::info('Webhook de Mercado Pago: el pedido ya estaba cobrado, se ignora.', ['intento_id' => $intento->id]);

            $intento->update(['estado' => IntentoPagoMercadopago::ESTADO_APROBADO, 'mp_merchant_order_id' => $ordenId]);

            return;
        }

        try {
            $factura = $this->procesador->cobrar(
                $pedido,
                $cajero,
                [
                    'efectivo' => (float) ($montos['monto_efectivo'] ?? 0),
                    'tarjeta' => (float) ($montos['monto_tarjeta'] ?? 0),
                    'transferencia' => (float) ($montos['monto_transferencia'] ?? 0),
                    'mercadopago' => (float) $intento->monto,
                ],
                (string) ($montos['tipo_comprobante'] ?? 'remito'),
                isset($montos['cliente_id']) ? (int) $montos['cliente_id'] : null,
                (filled($montos['cliente_nombre'] ?? null) && filled($montos['cliente_cuit'] ?? null)) ? [
                    'nombre' => $montos['cliente_nombre'],
                    'cuit' => $montos['cliente_cuit'],
                    'telefono' => $montos['cliente_telefono'] ?? null,
                ] : null,
                $montos['tipo_factura'] ?? null,
                (float) ($montos['monto_fiado'] ?? 0),
            );
        } catch (\RuntimeException $e) {
            // Mercado Pago YA acreditó el pago de verdad, pero algo local
            // impidió cerrar la venta (caja cerrada, sin stock, etc.) —
            // esto NO es idempotencia, es plata real recibida que no quedó
            // reflejada. Se loguea como error (no como info) y el intento
            // queda en un estado distinto para poder encontrarlo y
            // resolverlo a mano — nunca se marca "aprobado" como si la
            // venta se hubiera cerrado, porque no se cerró.
            Log::error('Webhook de Mercado Pago: el pago se acreditó pero no se pudo cerrar la venta localmente.', [
                'intento_id' => $intento->id,
                'pedido_id' => $pedido->id,
                'mensaje' => $e->getMessage(),
            ]);

            $intento->update(['estado' => IntentoPagoMercadopago::ESTADO_ERROR_AL_COBRAR, 'mp_merchant_order_id' => $ordenId]);

            return;
        }

        $pedido->refresh();

        if ($factura) {
            $this->emisor->emitir($factura, $pedido);
        }

        $intento->update(['estado' => IntentoPagoMercadopago::ESTADO_APROBADO, 'mp_merchant_order_id' => $ordenId]);
    }
}
