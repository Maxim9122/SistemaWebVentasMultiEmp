<?php

namespace App\Services\MercadoPago;

use App\Models\Empresa;
use App\Models\PagoQrMercadoPago;
use App\Models\Pedido;
use App\Models\User;
use App\Services\Facturacion\EmisionComprobanteService;
use App\Services\ProcesadorDeCobro;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Orquesta el cobro con QR de Mercado Pago: crea la "intención" de pago
 * (antes de tocar cobrar(), el pedido no se modifica todavía) y, cuando el
 * webhook confirma que Mercado Pago aprobó el pago, recién ahí dispara el
 * ProcesadorDeCobro::cobrar() de siempre — sin cambiarle una línea.
 */
class PagoQrService
{
    private const MINUTOS_EXPIRACION = 15;

    public function __construct(
        private readonly MercadoPagoApiClient $client,
        private readonly OnboardingMercadoPagoService $onboarding,
        private readonly ProcesadorDeCobro $procesador,
        private readonly EmisionComprobanteService $emisor,
    ) {}

    /**
     * Reusa un intento pendiente y todavía no vencido si ya hay uno para
     * este pedido (evita generar una preferencia nueva en Mercado Pago
     * cada vez que el cajero recarga la pantalla) — si no, crea uno.
     *
     * $tipoComprobante/$clienteId/$clienteNuevo/$tipoFactura son exactamente
     * lo que cobrar() necesita además de los montos — se capturan acá (mismos
     * datos que el cajero ya cargó en el formulario de cobro) porque el
     * webhook que va a confirmar el pago no tiene sesión ni el form original.
     *
     * @param  array{nombre: string, cuit: string, telefono?: string|null}|null  $clienteNuevo
     */
    public function crearIntento(
        Pedido $pedido,
        User $cajero,
        string $tipoComprobante = 'remito',
        ?int $clienteId = null,
        ?array $clienteNuevo = null,
        ?string $tipoFactura = null,
    ): PagoQrMercadoPago {
        $existente = PagoQrMercadoPago::where('pedido_id', $pedido->id)
            ->where('estado', PagoQrMercadoPago::ESTADO_PENDIENTE)
            ->where('expira_at', '>', now())
            ->latest()
            ->first();

        if ($existente) {
            return $existente;
        }

        $empresa = $pedido->empresa;
        $credencial = $this->credencialActiva($empresa);
        $accessToken = $this->tokenVigente($credencial);

        $intento = PagoQrMercadoPago::create([
            'empresa_id' => $empresa->id,
            'pedido_id' => $pedido->id,
            'user_id' => $cajero->id,
            'monto' => $pedido->total,
            'external_reference' => (string) Str::uuid(),
            'expira_at' => now()->addMinutes(self::MINUTOS_EXPIRACION),
            'tipo_comprobante' => $tipoComprobante,
            'cliente_id' => $clienteId,
            'cliente_nombre_nuevo' => $clienteNuevo['nombre'] ?? null,
            'cliente_cuit_nuevo' => $clienteNuevo['cuit'] ?? null,
            'cliente_telefono_nuevo' => $clienteNuevo['telefono'] ?? null,
            'tipo_factura' => $tipoFactura,
        ]);

        // notification_url lleva el id del intento en el query string — así
        // el webhook lo identifica sin ambigüedad apenas llega, sin tener
        // que adivinar por CUIT ni por ids reutilizados (a diferencia del
        // webhook de comprobantes de AFIP, acá controlamos nosotros mismos
        // la URL exacta que le damos a Mercado Pago).
        $respuesta = $this->client->crearPreferencia($accessToken, [
            'items' => [[
                'title' => "Venta #{$pedido->id}",
                'quantity' => 1,
                'unit_price' => (float) $pedido->total,
                'currency_id' => 'ARS',
            ]],
            'external_reference' => $intento->external_reference,
            'notification_url' => route('webhooks.mercadopago', ['intento' => $intento->id]),
            'expires' => true,
            'expiration_date_to' => $intento->expira_at->toIso8601String(),
        ]);

        $intento->update([
            'preference_id' => $respuesta['id'] ?? null,
            'init_point' => $respuesta['init_point'] ?? null,
        ]);

        return $intento->fresh();
    }

    /**
     * El QR se genera 100% local con `endroid/qr-code` (mismo criterio que
     * el QR de AFIP en ComprobantePdfService) — el navegador nunca habla
     * directo con Mercado Pago, todo pasa por nuestro servidor.
     */
    public function generarQr(PagoQrMercadoPago $intento): ?string
    {
        if (! $intento->init_point) {
            return null;
        }

        $qrCode = new QrCode(data: $intento->init_point, size: 260, margin: 8);

        return (new PngWriter())->write($qrCode)->getDataUri();
    }

    public function cancelar(PagoQrMercadoPago $intento): void
    {
        if ($intento->estado === PagoQrMercadoPago::ESTADO_PENDIENTE) {
            $intento->update(['estado' => PagoQrMercadoPago::ESTADO_CANCELADO]);
        }
    }

    /**
     * Se llama desde el webhook cuando Mercado Pago avisa que un pago
     * cambió de estado. Contrato: NUNCA tira excepción (mismo criterio que
     * EmisionComprobanteService::emitir()) — cualquier problema queda
     * logueado, nunca hace explotar la respuesta al webhook.
     */
    public function confirmar(PagoQrMercadoPago $intento, string $paymentId): void
    {
        if ($intento->estado !== PagoQrMercadoPago::ESTADO_PENDIENTE) {
            return; // ya se resolvió antes (reintento del webhook) — no hacer nada de nuevo.
        }

        if ($intento->expirado()) {
            $intento->update(['estado' => PagoQrMercadoPago::ESTADO_EXPIRADO]);

            return;
        }

        $empresa = $intento->empresa;
        $credencial = $empresa->credencialMercadoPago;

        if (! $credencial || ! $credencial->estaActiva()) {
            Log::warning('Pago QR MP: llegó confirmación pero la empresa ya no tiene credencial activa.', ['intento_id' => $intento->id]);

            return;
        }

        try {
            $accessToken = $this->tokenVigente($credencial);
            $pago = $this->client->consultarPago($accessToken, $paymentId);
        } catch (\Throwable $e) {
            Log::error('Pago QR MP: error consultando el pago contra la API.', ['intento_id' => $intento->id, 'exception' => $e]);

            return;
        }

        $intento->update(['payment_id' => $paymentId, 'datos_respuesta' => $pago]);

        $status = $pago['status'] ?? null;

        if ($status === 'rejected' || $status === 'cancelled') {
            $intento->update(['estado' => PagoQrMercadoPago::ESTADO_RECHAZADO]);

            return;
        }

        if ($status !== 'approved') {
            // pending / in_process / authorized: todavía no está resuelto,
            // no se toca el estado — el polling y el webhook van a volver a
            // pasar por acá cuando cambie de verdad.
            return;
        }

        // Nunca confiar en el monto del webhook a secas: el que importa es
        // el que devuelve la propia consulta a la API, recién hecha arriba.
        $montoAprobado = round((float) ($pago['transaction_amount'] ?? 0), 2);

        if (bccomp((string) $montoAprobado, (string) $intento->monto, 2) !== 0) {
            Log::warning('Pago QR MP: el monto aprobado no coincide con el esperado — no se cobra.', [
                'intento_id' => $intento->id,
                'esperado' => (float) $intento->monto,
                'recibido' => $montoAprobado,
            ]);

            $intento->update(['estado' => PagoQrMercadoPago::ESTADO_RECHAZADO]);

            return;
        }

        $pedido = $intento->pedido;
        $cajero = $intento->usuario;

        $clienteNuevo = $intento->cliente_nombre_nuevo && $intento->cliente_cuit_nuevo ? [
            'nombre' => $intento->cliente_nombre_nuevo,
            'cuit' => $intento->cliente_cuit_nuevo,
            'telefono' => $intento->cliente_telefono_nuevo,
        ] : null;

        try {
            $factura = $this->procesador->cobrar(
                $pedido,
                $cajero,
                ['mercadopago' => (float) $intento->monto],
                $intento->tipo_comprobante,
                $intento->cliente_id,
                $clienteNuevo,
                $intento->tipo_factura,
            );
        } catch (\RuntimeException $e) {
            // El pedido ya fue procesado (webhook duplicado, o se cobró por
            // otro medio mientras tanto) — el pago de Mercado Pago ya está
            // aprobado igual, queda logueado para que un admin lo revise a
            // mano si hiciera falta, pero no se puede hacer nada más acá.
            Log::warning('Pago QR MP: no se pudo cobrar el pedido (posible duplicado).', ['intento_id' => $intento->id, 'motivo' => $e->getMessage()]);
            $intento->update(['estado' => PagoQrMercadoPago::ESTADO_APROBADO]);

            return;
        }

        $intento->update(['estado' => PagoQrMercadoPago::ESTADO_APROBADO]);

        if ($factura) {
            $pedido->refresh();
            $this->emisor->emitir($factura, $pedido);
        }
    }

    private function credencialActiva(Empresa $empresa): \App\Models\CredencialMercadoPago
    {
        $credencial = $empresa->credencialMercadoPago;

        if (! $credencial || ! $credencial->estaActiva()) {
            throw new \RuntimeException('Esta empresa no tiene conectada su cuenta de Mercado Pago.');
        }

        return $credencial;
    }

    private function tokenVigente(\App\Models\CredencialMercadoPago $credencial): string
    {
        if ($credencial->tokenVencido()) {
            $credencial = $this->onboarding->refrescarToken($credencial);
        }

        return $credencial->access_token;
    }
}
