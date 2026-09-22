<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use App\Models\Pedido;
use App\Services\Facturacion\EmisionComprobanteService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Recibe el aviso de que un comprobante 202 (pendiente) se resolvió. Ruta
 * pública sin sesión — primero se identifica a qué empresa pertenece la
 * factura y RECIÉN AHÍ se verifica la firma con el webhook_secret propio de
 * esa empresa (no el de onboarding, que es otro secreto).
 */
class FacturacionComprobantesWebhookController extends Controller
{
    public function __invoke(Request $request, EmisionComprobanteService $emisor): Response
    {
        $factura = $this->localizarFactura(
            $request->input('comprobante_id') ?? $request->input('id'),
            $request->input('idempotency_key'),
        );

        if (! $factura) {
            Log::warning('Webhook de comprobantes: no se pudo identificar la factura.', $request->all());

            return response()->noContent();
        }

        $secret = $factura->empresa->credencialFacturacion?->webhook_secret;

        if (! $secret || ! $this->firmaValida($request, $secret)) {
            $this->logFirmaInvalida($request, $factura, $secret);
            abort(401);
        }

        $emisor->aplicarResultadoWebhook($factura, $request->all());

        return response()->noContent();
    }

    private function localizarFactura(mixed $comprobanteExternoId, ?string $idempotencyKey): ?Factura
    {
        if ($comprobanteExternoId) {
            $factura = Factura::where('comprobante_externo_id', $comprobanteExternoId)->first();

            if ($factura) {
                return $factura;
            }
        }

        if ($idempotencyKey && str_starts_with($idempotencyKey, 'venta-')) {
            $pedidoId = (int) str_replace('venta-', '', $idempotencyKey);

            return Pedido::find($pedidoId)?->factura;
        }

        return null;
    }

    private function firmaValida(Request $request, string $secret): bool
    {
        $firmaEsperada = hash_hmac('sha256', $request->getContent(), $secret);
        $firmaRecibida = (string) $request->header('X-Signature');

        return $firmaRecibida !== '' && hash_equals($firmaEsperada, $firmaRecibida);
    }

    private function logFirmaInvalida(Request $request, Factura $factura, ?string $secret): void
    {
        $body = $request->getContent();

        Log::warning('Webhook de comprobantes con firma inválida.', [
            'factura_id' => $factura->id,
            'content_type' => $request->header('Content-Type'),
            'body_length' => strlen($body),
            'body_preview' => json_decode($body, true) ?? substr($body, 0, 300),
            'x_signature_recibida' => $request->header('X-Signature'),
            'x_signature_esperada' => $secret ? hash_hmac('sha256', $body, $secret) : '(sin secreto)',
        ]);
    }
}
