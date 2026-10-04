<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\MercadoPago\MercadoPagoWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Notificación de pago del QR fijo de Mercado Pago. Ruta pública sin
 * sesión — el secreto es uno solo por Application (no por empresa), así que
 * la firma se verifica antes de mirar nada del payload (ver
 * MercadoPagoWebhookService). Siempre devuelve 2xx salvo que la firma sea
 * inválida (Mercado Pago reintenta si no le devolvemos 2xx a tiempo —
 * cualquier falla interna se loguea, nunca se refleja en el status code).
 */
class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request, MercadoPagoWebhookService $servicio): Response
    {
        $secret = config('services.mercadopago.webhook_secret');

        if (! $secret || ! $servicio->firmaValida($request, $secret)) {
            Log::warning('Webhook de Mercado Pago con firma inválida.', [
                'query' => $request->query(),
                'x_signature' => $request->header('x-signature'),
                'x_request_id' => $request->header('x-request-id'),
            ]);

            abort(401);
        }

        try {
            $servicio->procesarNotificacion($request->json()->all());
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->noContent();
    }
}
