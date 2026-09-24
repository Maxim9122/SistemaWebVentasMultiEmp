<?php

namespace App\Services\MercadoPago;

use Illuminate\Support\Facades\Http;

/**
 * Capa HTTP pura sobre la API de Mercado Pago (OAuth + Checkout API). No
 * tiene lógica de negocio propia — solo arma y manda los requests. El
 * token de cada empresa siempre entra como parámetro, nunca se lee acá de
 * la base (mismo criterio que FacturacionApiClient).
 */
class MercadoPagoApiClient
{
    private const BASE_URL = 'https://api.mercadopago.com';

    public function configurada(): bool
    {
        return filled(config('services.mercadopago.client_id')) && filled(config('services.mercadopago.client_secret'));
    }

    /**
     * Canjea el `code` que Mercado Pago mandó al callback de OAuth por los
     * tokens de la cuenta que se conectó.
     *
     * @return array{access_token: string, refresh_token: string, user_id: int, expires_in: int}
     */
    public function intercambiarCodigoOAuth(string $code, string $redirectUri): array
    {
        $this->asegurarConfigurada();

        return Http::asForm()->post(self::BASE_URL.'/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.mercadopago.client_id'),
            'client_secret' => config('services.mercadopago.client_secret'),
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ])->throw()->json();
    }

    /**
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    public function refrescarToken(string $refreshToken): array
    {
        $this->asegurarConfigurada();

        return Http::asForm()->post(self::BASE_URL.'/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => config('services.mercadopago.client_id'),
            'client_secret' => config('services.mercadopago.client_secret'),
            'refresh_token' => $refreshToken,
        ])->throw()->json();
    }

    /**
     * Crea la preferencia de pago (Checkout API) por el monto exacto de la
     * venta. `$body` trae `items`, `external_reference` y `notification_url`
     * — ver PagoQrService. Devuelve el `id` de la preferencia y el
     * `init_point`, que es la URL que se convierte en QR.
     */
    public function crearPreferencia(string $accessToken, array $body): array
    {
        $this->asegurarConfigurada();

        return Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(15)
            ->post(self::BASE_URL.'/checkout/preferences', $body)
            ->throw()
            ->json();
    }

    /**
     * Consulta el estado real de un pago contra la API — nunca se confía
     * en lo que venga en el body del webhook a secas, esta es la fuente de
     * verdad.
     */
    public function consultarPago(string $accessToken, string $paymentId): array
    {
        $this->asegurarConfigurada();

        return Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(15)
            ->get(self::BASE_URL."/v1/payments/{$paymentId}")
            ->throw()
            ->json();
    }

    private function asegurarConfigurada(): void
    {
        if (! $this->configurada()) {
            throw new \RuntimeException('MERCADOPAGO_CLIENT_ID/MERCADOPAGO_CLIENT_SECRET no están configurados.');
        }
    }
}
