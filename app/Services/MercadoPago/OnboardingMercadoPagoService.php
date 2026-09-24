<?php

namespace App\Services\MercadoPago;

use App\Models\CredencialMercadoPago;
use App\Models\Empresa;

/**
 * A diferencia de AFIP, acá no hace falta un webhook de onboarding aparte:
 * Mercado Pago usa OAuth estándar (Authorization Code) — la empresa
 * autoriza en Mercado Pago, vuelve a nuestro propio callback con un
 * `code`, y se canjea por los tokens en la misma request.
 */
class OnboardingMercadoPagoService
{
    public function __construct(private readonly MercadoPagoApiClient $client) {}

    /**
     * Arma la URL de autorización de Mercado Pago. El `state` va cifrado
     * (no es solo el empresa_id en texto plano) para que el callback no se
     * pueda usar para pegarle el token de una cuenta ajena a otra empresa —
     * se valida al volver, ver completarAutorizacion().
     */
    public function urlAutorizacion(Empresa $empresa, string $redirectUri): string
    {
        $state = encrypt(['empresa_id' => $empresa->id, 'ts' => now()->timestamp]);

        $query = http_build_query([
            'client_id' => config('services.mercadopago.client_id'),
            'response_type' => 'code',
            'platform_id' => 'mp',
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);

        return "https://auth.mercadopago.com/authorization?{$query}";
    }

    public function decodificarState(string $state): ?int
    {
        try {
            $datos = decrypt($state);
        } catch (\Throwable) {
            return null;
        }

        // 10 minutos de margen: tiempo de sobra para que el dueño de la
        // empresa autorice en la pantalla de Mercado Pago, pero un `state`
        // viejo reusado (por ejemplo, un link guardado) deja de servir.
        if (! is_array($datos) || ! isset($datos['empresa_id'], $datos['ts']) || now()->timestamp - $datos['ts'] > 600) {
            return null;
        }

        return (int) $datos['empresa_id'];
    }

    public function completarAutorizacion(Empresa $empresa, string $code, string $redirectUri): void
    {
        $respuesta = $this->client->intercambiarCodigoOAuth($code, $redirectUri);

        CredencialMercadoPago::updateOrCreate(
            ['empresa_id' => $empresa->id],
            [
                // Mercado Pago no tiene un paso de "elegir ambiente" en el
                // OAuth (a diferencia de AFIP): la cuenta que se conecta acá
                // es siempre la cuenta real de la empresa. Para probar sin
                // mover plata real, Mercado Pago usa "usuarios de prueba"
                // (cuentas de test creadas aparte, no un flag de ambiente) —
                // este campo queda informativo.
                'ambiente' => 'produccion',
                'mp_user_id' => $respuesta['user_id'] ?? null,
                'access_token' => $respuesta['access_token'],
                'refresh_token' => $respuesta['refresh_token'] ?? null,
                'token_expira_en' => isset($respuesta['expires_in']) ? now()->addSeconds((int) $respuesta['expires_in']) : null,
                'estado' => CredencialMercadoPago::ESTADO_ACTIVA,
            ],
        );
    }

    /**
     * Se llama antes de cualquier uso del token si tokenVencido() da true.
     * Actualiza la credencial con el token nuevo.
     */
    public function refrescarToken(CredencialMercadoPago $credencial): CredencialMercadoPago
    {
        $respuesta = $this->client->refrescarToken($credencial->refresh_token);

        $credencial->update([
            'access_token' => $respuesta['access_token'],
            'refresh_token' => $respuesta['refresh_token'] ?? $credencial->refresh_token,
            'token_expira_en' => isset($respuesta['expires_in']) ? now()->addSeconds((int) $respuesta['expires_in']) : null,
        ]);

        return $credencial;
    }
}
