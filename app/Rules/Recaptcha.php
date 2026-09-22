<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifica el "No soy un robot" de Google reCAPTCHA v2 contra el endpoint
 * oficial de Google — sin paquete de terceros, es un solo POST (mismo
 * criterio de dependencias mínimas que el resto del proyecto).
 *
 * Mientras no haya RECAPTCHA_SECRET_KEY configurada, no hace nada (no
 * bloquea el registro) — mismo patrón "no-op silencioso" que la
 * facturación electrónica mientras no está configurada.
 */
class Recaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secretKey = config('services.recaptcha.secret_key');

        if (blank($secretKey)) {
            return;
        }

        if (blank($value)) {
            $fail('Confirmá que no sos un robot.');

            return;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $secretKey,
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            $fail('No pudimos verificar el captcha ahora mismo. Probá de nuevo en unos minutos.');

            return;
        }

        if (! $response->successful() || ! ($response->json('success') ?? false)) {
            Log::warning('reCAPTCHA rechazado en registro de empresa.', [
                'ip' => request()->ip(),
                'error_codes' => $response->json('error-codes'),
            ]);

            $fail('No pudimos confirmar que no sos un robot. Volvé a intentarlo.');
        }
    }
}
