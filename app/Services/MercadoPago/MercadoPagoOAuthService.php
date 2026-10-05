<?php

namespace App\Services\MercadoPago;

use App\Models\CredencialMercadoPago;
use App\Models\Empresa;

/**
 * Conectar la cuenta de Mercado Pago de una empresa por OAuth (Authorization
 * Code) — cada empresa autoriza su propia cuenta, nunca se pega un token a
 * mano. El "state" va cifrado con el helper estándar de Laravel (nada de
 * dependencias nuevas) para que el callback no pueda usarse para pegarle el
 * token de una empresa a otra.
 */
class MercadoPagoOAuthService
{
    public function __construct(private readonly MercadoPagoApiClient $cliente) {}

    public function urlDeAutorizacion(Empresa $empresa, bool $sandbox = false): string
    {
        $state = encrypt(['empresa_id' => $empresa->id, 'sandbox' => $sandbox]);

        return 'https://auth.mercadopago.com/authorization?'.http_build_query([
            'client_id' => config('services.mercadopago.client_id'),
            'response_type' => 'code',
            'platform_id' => 'mp',
            'redirect_uri' => config('services.mercadopago.redirect_uri'),
            'state' => $state,
        ]);
    }

    /**
     * Devuelve [empresa_id, sandbox] codificado en el state, o null si es
     * inválido (vencido, manipulado, o de otra instalación) — el caller
     * decide qué hacer con un state inválido.
     *
     * @return array{empresa_id: int, sandbox: bool}|null
     */
    public function datosDesdeState(string $state): ?array
    {
        try {
            $datos = decrypt($state);
        } catch (\Throwable) {
            return null;
        }

        if (! isset($datos['empresa_id'])) {
            return null;
        }

        return ['empresa_id' => $datos['empresa_id'], 'sandbox' => $datos['sandbox'] ?? false];
    }

    public function manejarCallback(Empresa $empresa, string $code, bool $sandbox = false): CredencialMercadoPago
    {
        $respuesta = $this->cliente->intercambiarCodigoPorToken(
            $code,
            (string) config('services.mercadopago.redirect_uri'),
            $sandbox,
        );

        return CredencialMercadoPago::updateOrCreate(
            ['empresa_id' => $empresa->id],
            [
                'ambiente' => $sandbox ? 'sandbox' : 'produccion',
                'mp_user_id' => $respuesta['user_id'] ?? null,
                'access_token' => $respuesta['access_token'] ?? null,
                'refresh_token' => $respuesta['refresh_token'] ?? null,
                'token_expira_en' => isset($respuesta['expires_in'])
                    ? now()->addSeconds((int) $respuesta['expires_in'])
                    : null,
                'estado' => CredencialMercadoPago::ESTADO_ACTIVA,
                // La Sucursal/Caja/QR quedan atadas a la cuenta de Mercado
                // Pago que las creó — si esto es una reconexión (cambio de
                // cuenta), el store_id/pos_id viejo pertenece a OTRA cuenta
                // y usarlo con el access_token nuevo lo rechaza Mercado
                // Pago. Se resetean siempre para forzar "Configurar caja y
                // generar QR" de nuevo, en vez de dejar un QR que ya no
                // funciona mostrado como si estuviera bien.
                'mp_store_id' => null,
                'mp_pos_id' => null,
                'mp_external_store_id' => null,
                'mp_external_pos_id' => null,
                'mp_qr_image_url' => null,
                'mp_store_location' => null,
            ],
        );
    }

    public function asegurarTokenVigente(CredencialMercadoPago $credencial): CredencialMercadoPago
    {
        if (! $credencial->tokenVencido()) {
            return $credencial;
        }

        $respuesta = $this->cliente->refrescarToken((string) $credencial->refresh_token);

        $credencial->update([
            'access_token' => $respuesta['access_token'] ?? $credencial->access_token,
            'refresh_token' => $respuesta['refresh_token'] ?? $credencial->refresh_token,
            'token_expira_en' => isset($respuesta['expires_in'])
                ? now()->addSeconds((int) $respuesta['expires_in'])
                : $credencial->token_expira_en,
        ]);

        return $credencial->fresh();
    }
}
