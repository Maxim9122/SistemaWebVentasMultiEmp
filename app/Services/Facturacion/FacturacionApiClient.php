<?php

namespace App\Services\Facturacion;

use App\Services\Facturacion\Exceptions\CertificadoYaValidadoException;
use App\Services\Facturacion\Exceptions\CuitYaRegistradoException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Capa HTTP pura sobre la API de Facturación Electrónica AFIP/ARCA
 * (proyecto hermano ModuloAPI_Facturacion_ARCA). No tiene lógica de
 * negocio propia — solo arma y manda los requests.
 */
class FacturacionApiClient
{
    public function configurada(): bool
    {
        return filled(config('services.facturacion.url'));
    }

    /**
     * Paso 1: dar de alta una empresa y pedir su link de onboarding. Tira
     * CuitYaRegistradoException si el CUIT ya pertenece a una empresa
     * registrada por otra integración o cargada a mano del otro lado (409—
     * no se tocó nada de esa empresa). Tira \RuntimeException/RequestException
     * si falla por cualquier otro motivo (config faltante, red, otro error
     * HTTP) — el caller decide qué hacer en cualquiera de los dos casos.
     *
     * @return array{empresa_id: int, ambiente: string, onboarding_url: string, expira_en: string}
     */
    public function altaEmpresa(array $datos): array
    {
        $this->asegurarConfigurada();

        $response = Http::withToken(config('services.facturacion.platform_key'))
            ->acceptJson()
            ->post($this->url('/api/platform/v1/empresas'), $datos);

        if ($response->status() === 409) {
            throw new CuitYaRegistradoException(
                'Este CUIT ya está registrado con otra integración o cargado a mano del lado de la API de facturación. Contactá al administrador de esa API para resolverlo.'
            );
        }

        return $response->throw()->json();
    }

    /**
     * Corrige razón social/CUIT/email de una empresa que registramos, antes
     * de que suba su certificado (cualquier subconjunto de los tres campos).
     * No hace falta pedir un onboarding_url nuevo después: si el link
     * original sigue vigente, valida el certificado contra el CUIT ya
     * corregido en el momento de subirlo.
     *
     * Tira CertificadoYaValidadoException si la empresa ya tiene un
     * certificado AFIP validado (409, cambio de identidad ya no permitido
     * por acá — no es transitorio, no reintentar). Tira
     * CuitYaRegistradoException si el CUIT nuevo ya es de otra empresa
     * (409). Tira \RuntimeException si el empresa_id no existe o no lo
     * registró esta plataforma (404), o si los datos no pasan la
     * validación del otro lado (422).
     *
     * @param  array{razon_social?: string, cuit?: string, email_contacto?: string}  $datos
     * @return array{empresa_id: int, razon_social: string, cuit: string, email_contacto: string}
     */
    public function corregirEmpresa(int $empresaExternaId, array $datos): array
    {
        $this->asegurarConfigurada();

        $response = Http::withToken(config('services.facturacion.platform_key'))
            ->acceptJson()
            ->patch($this->url("/api/platform/v1/empresas/{$empresaExternaId}"), $datos);

        if ($response->status() === 409) {
            $mensaje = (string) ($response->json('message') ?? '');

            // El contrato de este endpoint distingue los dos casos de 409 solo
            // por el texto del mensaje, no por un código aparte — si cambia la
            // redacción del otro lado, hay que revisar este chequeo.
            if (str_contains(mb_strtolower($mensaje), 'certificado')) {
                throw new CertificadoYaValidadoException(
                    $mensaje !== '' ? $mensaje : 'Esta empresa ya tiene un certificado AFIP validado — no se puede corregir por acá. Contactá al administrador de la API de facturación.'
                );
            }

            throw new CuitYaRegistradoException(
                $mensaje !== '' ? $mensaje : 'Ese CUIT ya pertenece a otra empresa registrada.'
            );
        }

        if ($response->status() === 404) {
            throw new \RuntimeException('No se encontró esa empresa en la API de facturación, o no fue esta integración la que la registró.');
        }

        if ($response->status() === 422) {
            throw new \RuntimeException((string) ($response->json('message') ?? 'Los datos no son válidos para la API de facturación.'));
        }

        return $response->throw()->json();
    }

    /**
     * Paso 3: emitir un comprobante. NO tira excepción por respuestas 4xx/5xx
     * (el caller mapea el status code) — solo puede tirar por problemas de
     * red/timeout, responsabilidad del caller atraparlo.
     */
    public function emitirComprobante(string $apiKey, string $idempotencyKey, array $body): Response
    {
        $this->asegurarConfigurada();

        return Http::withToken($apiKey)
            ->withHeaders(['Idempotency-Key' => $idempotencyKey])
            ->acceptJson()
            ->timeout(15)
            ->post($this->url('/api/v1/comprobantes'), $body);
    }

    public function consultarComprobante(string $apiKey, int|string $comprobanteId): Response
    {
        $this->asegurarConfigurada();

        return Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(15)
            ->get($this->url("/api/v1/comprobantes/{$comprobanteId}"));
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.facturacion.url'), '/').$path;
    }

    private function asegurarConfigurada(): void
    {
        if (! $this->configurada()) {
            throw new \RuntimeException('FACTURACION_API_URL no está configurada.');
        }
    }
}
