<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\CredencialFacturacion;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Recibe la API Key y el punto de venta de una empresa cuando termina el
 * onboarding (Paso 2 del módulo de facturación). Ruta pública sin sesión —
 * la autenticidad se garantiza con la firma HMAC, no con auth de Laravel.
 */
class FacturacionOnboardingWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $secret = config('services.facturacion.onboarding_webhook_secret');

        if (! $secret || ! $this->firmaValida($request, $secret)) {
            $this->logFirmaInvalida($request, $secret);
            abort(401);
        }

        $payload = $request->validate([
            'onboarding_id' => ['required'],
            'empresa_id' => ['required'],
            'cuit' => ['required', 'string'],
            'ambiente' => ['required', 'string'],
            'punto_venta' => ['required', 'integer'],
            'renovacion' => ['nullable', 'boolean'],
            'api_key' => ['required', 'string'],
            'comprobantes_webhook_secret' => ['nullable', 'string'],
        ]);

        $empresa = $this->localizarEmpresa($payload);

        if (! $empresa) {
            Log::warning('Webhook de onboarding: no se pudo identificar una única empresa local para este pago.', [
                'cuit' => $payload['cuit'],
                'empresa_id_externo' => $payload['empresa_id'],
            ]);

            return response()->noContent();
        }

        // El upsert reemplaza siempre la api_key/webhook_secret existente de esta
        // empresa (empresa_id es único en esta tabla, nunca se suma una fila nueva)
        // — tanto para un onboarding inicial como para una renovación. En una
        // renovación (renovacion=true), la API Key anterior ya quedó revocada del
        // otro lado en el momento en que llega este webhook: si por lo que sea no
        // sobreescribiéramos acá, quedaríamos guardando una key ya muerta.
        CredencialFacturacion::updateOrCreate(
            ['empresa_id' => $empresa->id],
            [
                'ambiente' => $payload['ambiente'],
                'punto_venta' => $payload['punto_venta'],
                'api_key' => $payload['api_key'],
                'webhook_secret' => $payload['comprobantes_webhook_secret'] ?? null,
                'estado' => CredencialFacturacion::ESTADO_ACTIVA,
                'onboarding_id' => $payload['onboarding_id'],
                'empresa_externa_id' => $payload['empresa_id'],
            ],
        );

        Log::info(
            ($payload['renovacion'] ?? false)
                ? 'Certificado de facturación renovado — API Key anterior revocada del otro lado.'
                : 'Certificado de facturación configurado.',
            ['empresa_id' => $empresa->id, 'ambiente' => $payload['ambiente']],
        );

        return response()->noContent();
    }

    /**
     * Identifica a qué empresa local corresponde este webhook, en tres
     * pasos de precisión decreciente:
     *
     * 1. Por `onboarding_id` — el id de la solicitud puntual del otro lado,
     *    guardado en `credenciales_facturacion` ya desde el Paso 1
     *    (`OnboardingFacturacionService::iniciar()`). Es la única de las
     *    tres formas que NUNCA es ambigua: dos empresas locales con el
     *    mismo CUIT (el superadmin lo permite a criterio propio) pueden
     *    perfectamente compartir la MISMA empresa del lado de la API
     *    externa — la reutiliza entre varias solicitudes de esta plataforma
     *    para el mismo CUIT (ej. varios puntos de venta, o una renovación)
     *    — pero cada solicitud de onboarding tiene su propio id siempre.
     * 2. Por `empresa_externa_id` (el id de esa empresa reutilizable del
     *    otro lado) — solo como respaldo para onboardings iniciados antes
     *    de este cambio, que no tienen `onboarding_id` guardado. Achica el
     *    universo pero puede seguir siendo ambiguo (por la reutilización de
     *    arriba), así que solo se usa si resuelve a una única empresa.
     * 3. Por CUIT — último recurso, únicamente si identifica una sola
     *    empresa sin ambigüedad.
     *
     * Ninguno de los tres pasos adivina cuando hay más de una coincidencia
     * — mejor no aplicar la credencial que aplicarla a la empresa
     * equivocada.
     */
    private function localizarEmpresa(array $payload): ?Empresa
    {
        $porOnboardingId = CredencialFacturacion::where('onboarding_id', $payload['onboarding_id'])->get();

        if ($porOnboardingId->count() === 1) {
            return $porOnboardingId->first()->empresa;
        }

        if ($porOnboardingId->count() > 1) {
            return null;
        }

        $porEmpresaExterna = CredencialFacturacion::where('empresa_externa_id', $payload['empresa_id'])->get();

        if ($porEmpresaExterna->count() === 1) {
            return $porEmpresaExterna->first()->empresa;
        }

        if ($porEmpresaExterna->count() > 1) {
            return null;
        }

        $empresasConEseCuit = Empresa::where('cuit', $payload['cuit'])->get();

        return $empresasConEseCuit->count() === 1 ? $empresasConEseCuit->first() : null;
    }

    private function firmaValida(Request $request, string $secret): bool
    {
        $firmaEsperada = hash_hmac('sha256', $request->getContent(), $secret);
        $firmaRecibida = (string) $request->header('X-Signature');

        return $firmaRecibida !== '' && hash_equals($firmaEsperada, $firmaRecibida);
    }

    /**
     * Diagnóstico para cuando falla la firma: loguea la firma calculada vs
     * la recibida (son hashes, no secretos, seguro compararlos en el log) y
     * un preview del body con los campos sensibles redactados, para poder
     * distinguir sin adivinar entre secreto mal cargado, Content-Type
     * inesperado, o un payload con forma distinta a la documentada.
     */
    private function logFirmaInvalida(Request $request, ?string $secret): void
    {
        $body = $request->getContent();
        $payload = json_decode($body, true);

        if (is_array($payload)) {
            foreach (['api_key', 'comprobantes_webhook_secret'] as $campoSensible) {
                if (array_key_exists($campoSensible, $payload)) {
                    $payload[$campoSensible] = '[redactado]';
                }
            }
        }

        Log::warning('Webhook de onboarding con firma inválida.', [
            'content_type' => $request->header('Content-Type'),
            'body_length' => strlen($body),
            'body_preview' => $payload ?? substr($body, 0, 300),
            'x_signature_recibida' => $request->header('X-Signature'),
            'x_signature_esperada' => $secret ? hash_hmac('sha256', $body, $secret) : '(sin secreto configurado)',
        ]);
    }
}
