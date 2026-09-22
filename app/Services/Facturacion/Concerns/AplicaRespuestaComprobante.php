<?php

namespace App\Services\Facturacion\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;

/**
 * Mapeo de la respuesta de POST /api/v1/comprobantes a los campos de estado,
 * compartido entre Factura y NotaCredito — ambas emiten contra el mismo
 * endpoint y guardan exactamente el mismo set de campos (estado, cae,
 * cae_vencimiento, numero_comprobante, comprobante_externo_id, error_mensaje).
 */
trait AplicaRespuestaComprobante
{
    private function aplicarRespuestaComun(Model $comprobante, Response $response, string $estadoAprobada, string $estadoPendienteAfip, string $estadoRechazada, string $estadoError): void
    {
        $json = $response->json() ?? [];

        match ($response->status()) {
            200 => $comprobante->update([
                'estado' => $estadoAprobada,
                'cae' => $json['cae'] ?? null,
                'cae_vencimiento' => $json['cae_vencimiento'] ?? null,
                'numero_comprobante' => $json['numero_comprobante'] ?? null,
                'comprobante_externo_id' => $json['id'] ?? $json['comprobante_id'] ?? null,
                'error_mensaje' => null,
            ]),
            202 => $comprobante->update([
                'estado' => $estadoPendienteAfip,
                'comprobante_externo_id' => $json['id'] ?? $json['comprobante_id'] ?? null,
                'error_mensaje' => null,
            ]),
            422 => $comprobante->update([
                'estado' => $estadoRechazada,
                'error_mensaje' => ($json['error_mensaje'] ?? null) ?: 'Rechazado por AFIP.',
            ]),
            401 => $comprobante->update([
                'estado' => $estadoError,
                'error_mensaje' => 'La credencial de facturación fue rechazada. Contactá a soporte.',
            ]),
            default => $comprobante->update([
                'estado' => $estadoError,
                'error_mensaje' => "Respuesta inesperada del servicio de facturación (HTTP {$response->status()}).",
            ]),
        };
    }
}
