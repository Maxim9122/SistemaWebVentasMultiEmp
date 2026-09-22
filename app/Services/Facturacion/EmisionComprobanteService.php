<?php

namespace App\Services\Facturacion;

use App\Models\Factura;
use App\Models\Pedido;
use App\Services\Facturacion\Concerns\AplicaRespuestaComprobante;
use Illuminate\Support\Facades\Log;

/**
 * Emite comprobantes ante la API de Facturación Electrónica AFIP/ARCA.
 * Contrato: emitir() NUNCA tira excepción — una venta ya cobrada es dinero
 * real, la facturación es un paso posterior best-effort. Cualquier falla
 * queda grabada en la propia Factura (estado + error_mensaje) y logueada,
 * nunca revierte ni bloquea la venta.
 */
class EmisionComprobanteService
{
    use AplicaRespuestaComprobante;

    private const MAPA_TIPO_COMPROBANTE = [
        'A' => 1,
        'B' => 6,
        'C' => 11,
    ];

    public function __construct(
        private readonly FacturacionApiClient $client,
        private readonly CalculadoraIva $iva,
    ) {}

    public function emitir(Factura $factura, Pedido $pedido): void
    {
        if ($factura->cae !== null) {
            return;
        }

        if (! $this->client->configurada()) {
            Log::info("Facturación electrónica no configurada: factura #{$factura->id} queda pendiente.");

            return;
        }

        $credencial = $factura->empresa->credencialFacturacion;

        if (! $credencial || ! $credencial->estaActiva()) {
            $factura->update([
                'estado' => Factura::ESTADO_ERROR,
                'error_mensaje' => 'La empresa todavía no terminó de configurar la facturación electrónica (onboarding). Andá a Configuración y completá o reintentá ese paso.',
            ]);

            return;
        }

        $tipoComprobante = self::MAPA_TIPO_COMPROBANTE[$factura->tipo_factura] ?? null;

        if ($tipoComprobante === null) {
            $factura->update([
                'estado' => Factura::ESTADO_ERROR,
                'error_mensaje' => "Tipo de factura desconocido: \"{$factura->tipo_factura}\".",
            ]);

            return;
        }

        $docTipo = 99;
        $docNro = null;

        if (filled($factura->cliente_cuit)) {
            $docTipo = 80;
            $docNro = $factura->cliente_cuit;
        }

        $importe = (float) $pedido->total_cobrado;
        $esMonotributo = $factura->tipo_factura === 'C';
        $calculo = $esMonotributo ? $this->iva->sinDiscriminar($importe) : $this->iva->calcular($importe);

        $body = [
            'punto_venta' => $credencial->punto_venta,
            'tipo_comprobante' => $tipoComprobante,
            'concepto' => 1,
            'cliente_doc_tipo' => $docTipo,
            'cliente_doc_nro' => $docNro,
            'moneda' => 'PES',
            'cotizacion' => 1,
            'importe_neto' => $calculo['importe_neto'],
            'importe_iva' => $calculo['importe_iva'],
            'importe_total' => $calculo['importe_total'],
            'iva_detalle' => $esMonotributo ? [] : $this->iva->detalleParaComprobante($importe),
        ];

        try {
            $response = $this->client->emitirComprobante($credencial->api_key, 'venta-'.$pedido->id, $body);
        } catch (\Throwable $e) {
            Log::error("Error de red emitiendo comprobante para factura #{$factura->id}", ['exception' => $e]);

            $factura->update([
                'estado' => Factura::ESTADO_ERROR,
                'error_mensaje' => 'Error de comunicación con el servicio de facturación. Podés reintentar.',
            ]);

            return;
        }

        $this->aplicarRespuestaComun(
            $factura,
            $response,
            Factura::ESTADO_APROBADA,
            Factura::ESTADO_PENDIENTE_AFIP,
            Factura::ESTADO_RECHAZADA,
            Factura::ESTADO_ERROR,
        );
    }

    /**
     * Aplica el resultado que llega por el webhook de comprobantes, cuando
     * se resuelve un 202 pendiente.
     */
    public function aplicarResultadoWebhook(Factura $factura, array $payload): void
    {
        $estado = $payload['estado'] ?? null;

        if ($estado === 'aprobado' || filled($payload['cae'] ?? null)) {
            $factura->update([
                'estado' => Factura::ESTADO_APROBADA,
                'cae' => $payload['cae'] ?? $factura->cae,
                'cae_vencimiento' => $payload['cae_vencimiento'] ?? $factura->cae_vencimiento,
                'numero_comprobante' => $payload['numero_comprobante'] ?? $factura->numero_comprobante,
                'comprobante_externo_id' => $payload['comprobante_id'] ?? $payload['id'] ?? $factura->comprobante_externo_id,
                'error_mensaje' => null,
            ]);

            return;
        }

        if ($estado === 'rechazado') {
            $factura->update([
                'estado' => Factura::ESTADO_RECHAZADA,
                'error_mensaje' => ($payload['error_mensaje'] ?? null) ?: 'Rechazado por AFIP.',
            ]);

            return;
        }

        Log::warning('Webhook de comprobantes con estado no reconocido.', ['factura_id' => $factura->id, 'payload' => $payload]);
    }
}
