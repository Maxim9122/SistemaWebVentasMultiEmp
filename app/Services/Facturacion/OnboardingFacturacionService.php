<?php

namespace App\Services\Facturacion;

use App\Models\CredencialFacturacion;
use App\Models\Empresa;

class OnboardingFacturacionService
{
    public function __construct(private readonly FacturacionApiClient $client) {}

    /**
     * Da de alta la empresa en la plataforma de facturación (Paso 1) y
     * devuelve la URL de onboarding fresca para redirigir al dueño de la
     * empresa. Tira \RuntimeException si falla.
     */
    public function iniciar(Empresa $empresa, string $ambiente): string
    {
        $respuesta = $this->client->altaEmpresa([
            'razon_social' => $empresa->razon_social,
            'cuit' => $empresa->cuit,
            'email_contacto' => $empresa->email_contacto,
            'ambiente' => $ambiente,
            'webhook_url' => route('webhooks.facturacion.comprobantes'),
        ]);

        CredencialFacturacion::updateOrCreate(
            ['empresa_id' => $empresa->id],
            [
                'ambiente' => $ambiente,
                'estado' => CredencialFacturacion::ESTADO_PENDIENTE_ONBOARDING,
                'empresa_externa_id' => $respuesta['empresa_id'] ?? null,
                // Se guarda ya acá (no recién cuando llega el webhook) para
                // poder identificar sin ambigüedad, más tarde, a qué empresa
                // local corresponde ese webhook — `empresa_externa_id` NO
                // alcanza para eso: la API externa reutiliza la misma
                // empresa de su lado entre varias solicitudes nuestras para
                // el mismo CUIT (ej. varios puntos de venta), así que dos
                // empresas locales podrían terminar con el mismo
                // empresa_externa_id. `onboarding_id` es único por
                // solicitud, siempre. Ver FacturacionOnboardingWebhookController.
                'onboarding_id' => $respuesta['onboarding_id'] ?? null,
            ],
        );

        return $respuesta['onboarding_url'];
    }

    /**
     * Corrige razón social/CUIT/email de una empresa que ya se registró del
     * otro lado (tiene empresa_externa_id) pero todavía no terminó el
     * onboarding con un certificado validado. No toca CredencialFacturacion
     * — no hay nada que resincronizar ahí, esos datos viven solo en Empresa.
     *
     * @param  array{razon_social?: string, cuit?: string, email_contacto?: string}  $datos
     */
    public function corregirDatos(CredencialFacturacion $credencial, array $datos): void
    {
        if (! $credencial->empresa_externa_id) {
            throw new \RuntimeException('Esta empresa todavía no inició el alta en la API de facturación, no hay nada que corregir del otro lado.');
        }

        $this->client->corregirEmpresa($credencial->empresa_externa_id, $datos);
    }
}
