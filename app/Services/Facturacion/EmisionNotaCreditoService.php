<?php

namespace App\Services\Facturacion;

use App\Models\NotaCredito;
use App\Services\Facturacion\Concerns\AplicaRespuestaComprobante;
use Illuminate\Support\Facades\Log;

/**
 * Emite Notas de Crédito ante la API de Facturación Electrónica AFIP/ARCA —
 * mismo endpoint que las facturas (POST /api/v1/comprobantes), con
 * tipo_comprobante de NC y comprobante_asociado_id apuntando a la factura
 * que se acredita. Mismo contrato que EmisionComprobanteService: emitir()
 * NUNCA tira excepción, cualquier falla queda grabada en la propia
 * NotaCredito y es reintentable — la anulación local (stock, etc.) ya se
 * hizo antes de llegar acá y no se revierte por esto.
 */
class EmisionNotaCreditoService
{
    use AplicaRespuestaComprobante;

    // La letra tiene que coincidir con la de la factura que se acredita —
    // AFIP rechaza una NC A contra una Factura B, por ejemplo.
    private const MAPA_TIPO_NOTA_CREDITO = [
        'A' => 3,
        'B' => 8,
        'C' => 13,
    ];

    public function __construct(
        private readonly FacturacionApiClient $client,
        private readonly CalculadoraIva $iva,
    ) {}

    public function emitir(NotaCredito $notaCredito): void
    {
        if ($notaCredito->cae !== null) {
            return;
        }

        if (! $this->client->configurada()) {
            Log::info("Facturación electrónica no configurada: nota de crédito #{$notaCredito->id} queda pendiente.");

            return;
        }

        $factura = $notaCredito->factura;
        $credencial = $notaCredito->empresa->credencialFacturacion;

        if (! $credencial || ! $credencial->estaActiva()) {
            $notaCredito->update([
                'estado' => NotaCredito::ESTADO_ERROR,
                'error_mensaje' => 'La empresa todavía no terminó de configurar la facturación electrónica (onboarding). Andá a Configuración y completá o reintentá ese paso.',
            ]);

            return;
        }

        if (! $factura->comprobante_externo_id) {
            $notaCredito->update([
                'estado' => NotaCredito::ESTADO_ERROR,
                'error_mensaje' => 'La factura original no tiene un comprobante_externo_id guardado — no se puede acreditar.',
            ]);

            return;
        }

        // El (int) cast de más abajo trunca silenciosamente cualquier string no
        // numérico a 0 — sin este chequeo, un comprobante_externo_id con formato
        // inesperado mandaría "comprobante_asociado_id": 0 a AFIP en vez de
        // fallar acá con un error claro y local.
        if (! is_numeric($factura->comprobante_externo_id)) {
            $notaCredito->update([
                'estado' => NotaCredito::ESTADO_ERROR,
                'error_mensaje' => "El comprobante_externo_id de la factura original (\"{$factura->comprobante_externo_id}\") no es numérico — no se puede acreditar.",
            ]);

            return;
        }

        $tipoComprobante = self::MAPA_TIPO_NOTA_CREDITO[$factura->tipo_factura] ?? null;

        if ($tipoComprobante === null) {
            $notaCredito->update([
                'estado' => NotaCredito::ESTADO_ERROR,
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

        $importe = (float) $notaCredito->importe_acreditado;
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
            'comprobante_asociado_id' => (int) $factura->comprobante_externo_id,
        ];

        try {
            $response = $this->client->emitirComprobante($credencial->api_key, 'nc-'.$notaCredito->id, $body);
        } catch (\Throwable $e) {
            Log::error("Error de red emitiendo nota de crédito #{$notaCredito->id}", ['exception' => $e]);

            $notaCredito->update([
                'estado' => NotaCredito::ESTADO_ERROR,
                'error_mensaje' => 'Error de comunicación con el servicio de facturación. Podés reintentar.',
            ]);

            return;
        }

        $this->aplicarRespuestaComun(
            $notaCredito,
            $response,
            NotaCredito::ESTADO_APROBADA,
            NotaCredito::ESTADO_PENDIENTE_AFIP,
            NotaCredito::ESTADO_RECHAZADA,
            NotaCredito::ESTADO_ERROR,
        );
    }
}
