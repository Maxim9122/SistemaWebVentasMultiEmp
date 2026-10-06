<?php

namespace App\Services\MercadoPago;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Capa HTTP pura sobre la API de Mercado Pago — no tiene lógica de negocio
 * propia, no guarda estado. El token siempre entra como parámetro (nunca se
 * lee de la base acá adentro), mismo criterio que FacturacionApiClient.
 *
 * Endpoints y formas de body confirmados contra la documentación real de
 * Mercado Pago (vía el MCP Server oficial, no supuestos) el 2026-10-04.
 */
class MercadoPagoApiClient
{
    private const BASE_URL = 'https://api.mercadopago.com';

    public function configurada(): bool
    {
        return filled(config('services.mercadopago.client_id'))
            && filled(config('services.mercadopago.client_secret'));
    }

    /**
     * Canjea el "code" del callback de OAuth por un access_token/refresh_token.
     * Confirmado: el endpoint espera JSON, no form-urlencoded.
     */
    public function intercambiarCodigoPorToken(string $code, string $redirectUri, bool $sandbox = false): array
    {
        return Http::asJson()
            ->post(self::BASE_URL.'/oauth/token', [
                'client_id' => config('services.mercadopago.client_id'),
                'client_secret' => config('services.mercadopago.client_secret'),
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'test_token' => $sandbox ? 'true' : 'false',
            ])
            ->throw()
            ->json();
    }

    public function refrescarToken(string $refreshToken): array
    {
        return Http::asJson()
            ->post(self::BASE_URL.'/oauth/token', [
                'client_id' => config('services.mercadopago.client_id'),
                'client_secret' => config('services.mercadopago.client_secret'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ])
            ->throw()
            ->json();
    }

    /**
     * Sucursal — se crea una sola vez por empresa. location es obligatorio
     * según la documentación (street_number, street_name, city_name,
     * state_name, latitude, longitude, reference).
     */
    public function crearStore(string $accessToken, int $mpUserId, array $datos): array
    {
        return Http::withToken($accessToken)
            ->acceptJson()
            ->post(self::BASE_URL."/users/{$mpUserId}/stores", $datos)
            ->throw()
            ->json();
    }

    /**
     * Caja/POS — se crea una sola vez por empresa. El QR físico impreso
     * para este POS nunca cambia después de esto: la propia respuesta ya
     * trae la imagen lista en qr_response.image (no hace falta generarla).
     */
    public function crearPos(string $accessToken, array $datos): array
    {
        return Http::withToken($accessToken)
            ->acceptJson()
            ->withHeaders(['X-Idempotency-Key' => (string) Str::uuid()])
            ->post(self::BASE_URL.'/v2/pos', $datos)
            ->throw()
            ->json();
    }

    /**
     * Crea una orden de cobro (Orders API) con config.qr.mode = "static":
     * el monto queda asociado a la PRÓXIMA vez que se escanee el QR fijo ya
     * impreso de ese POS — no genera ninguna imagen nueva.
     */
    public function crearOrdenQr(string $accessToken, array $orden): array
    {
        return Http::withToken($accessToken)
            ->acceptJson()
            ->withHeaders(['X-Idempotency-Key' => (string) Str::uuid()])
            ->post(self::BASE_URL.'/v1/orders', $orden)
            ->throw()
            ->json();
    }

    public function consultarOrden(string $accessToken, string $ordenId): array
    {
        return Http::withToken($accessToken)
            ->acceptJson()
            ->get(self::BASE_URL."/v1/orders/{$ordenId}")
            ->throw()
            ->json();
    }

    public function cancelarOrden(string $accessToken, string $ordenId): array
    {
        return Http::withToken($accessToken)
            ->acceptJson()
            ->withHeaders(['X-Idempotency-Key' => (string) Str::uuid()])
            ->post(self::BASE_URL."/v1/orders/{$ordenId}/cancel")
            ->throw()
            ->json();
    }

    /**
     * Reembolso total de una orden ya pagada (sin body — un reembolso
     * parcial llevaría `transactions: [['id' => ..., 'amount' => ...]]`,
     * pero no lo necesitamos: la devolución es siempre de toda la venta).
     * Mercado Pago acepta esto hasta 360 días después del pago.
     */
    public function reembolsarOrden(string $accessToken, string $ordenId): array
    {
        return Http::withToken($accessToken)
            ->acceptJson()
            ->withHeaders(['X-Idempotency-Key' => (string) Str::uuid()])
            ->post(self::BASE_URL."/v1/orders/{$ordenId}/refund")
            ->throw()
            ->json();
    }
}
