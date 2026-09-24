<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\PagoQrMercadoPago;
use App\Services\MercadoPago\PagoQrService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Recibe el aviso de que un pago con QR cambió de estado. Ruta pública sin
 * sesión (routes/api.php) — la autenticidad se garantiza con la firma, no
 * con auth de Laravel.
 *
 * A diferencia de los webhooks de AFIP (donde hay que adivinar a qué
 * empresa pertenece el aviso), acá identificamos el intento directo por el
 * `?intento=` que nosotros mismos pusimos en la notification_url al crear
 * la preferencia — sin ambigüedad, sin tener que probar varias formas.
 *
 * La firma también es distinta a AFIP: Mercado Pago firma sobre un
 * "manifest" (id:<data.id>;request-id:<x-request-id>;ts:<ts>;), no sobre
 * el body crudo — y el secreto es uno solo por aplicación registrada (a
 * nivel plataforma, config/env), no uno por empresa. Por eso se puede
 * verificar la firma ANTES de identificar el intento (igual que el
 * webhook de onboarding de AFIP, no el de comprobantes).
 */
class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request, PagoQrService $servicio): Response
    {
        $secret = config('services.mercadopago.webhook_secret');

        if (! $secret || ! $this->firmaValida($request, $secret)) {
            $this->logFirmaInvalida($request, $secret);
            abort(401);
        }

        $intentoId = $request->query('intento');
        // OJO: Mercado Pago manda el parámetro como "data.id" en la URL,
        // pero PHP renombra automáticamente los puntos en nombres de query
        // string a guión bajo (parse_str) — $_GET/$request->query() nunca
        // va a tener la clave "data.id" literal, siempre "data_id".
        $paymentId = $request->query('data_id') ?? $request->input('data.id') ?? $request->input('id');

        $intento = $intentoId ? PagoQrMercadoPago::find($intentoId) : null;

        if (! $intento || ! $paymentId) {
            Log::warning('Webhook de Mercado Pago: no se pudo identificar el intento o el pago.', [
                'intento' => $intentoId,
                'payment_id' => $paymentId,
                'query' => $request->query(),
            ]);

            return response()->noContent();
        }

        $servicio->confirmar($intento, (string) $paymentId);

        return response()->noContent();
    }

    private function firmaValida(Request $request, string $secret): bool
    {
        $signatureHeader = (string) $request->header('x-signature');

        if ($signatureHeader === '') {
            return false;
        }

        $partes = [];

        foreach (explode(',', $signatureHeader) as $parte) {
            [$clave, $valor] = array_pad(explode('=', trim($parte), 2), 2, null);

            if ($clave !== null) {
                $partes[$clave] = $valor;
            }
        }

        $ts = $partes['ts'] ?? null;
        $v1 = $partes['v1'] ?? null;

        if (! $ts || ! $v1) {
            return false;
        }

        $requestId = (string) $request->header('x-request-id');
        $dataId = (string) ($request->query('data_id') ?? '');

        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $firmaEsperada = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($firmaEsperada, $v1);
    }

    private function logFirmaInvalida(Request $request, ?string $secret): void
    {
        Log::warning('Webhook de Mercado Pago con firma inválida.', [
            'x_signature' => $request->header('x-signature'),
            'x_request_id' => $request->header('x-request-id'),
            'query' => $request->query(),
            'tiene_secreto_configurado' => (bool) $secret,
        ]);
    }
}
