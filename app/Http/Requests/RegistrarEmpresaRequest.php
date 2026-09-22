<?php

namespace App\Http\Requests;

use App\Rules\Recaptcha;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RegistrarEmpresaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'razon_social' => ['required', 'string', 'max:255'],
            // A propósito sin `unique:empresas,cuit`: un CUIT repetido ya no
            // se bloquea acá en seco — se deja crear la solicitud (queda
            // `pendiente` como cualquier otra) y el superadmin ve un aviso
            // en el panel de aprobación para decidir con criterio (puede ser
            // la misma persona dando de alta un segundo local, o alguien
            // usando datos ajenos) en vez de que el sistema decida solo y en
            // silencio, sin que quede ningún rastro de que pasó.
            'cuit' => ['required', 'string', 'max:20'],
            'rubro' => ['nullable', 'string', 'max:255'],
            'email_contacto' => ['required', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * El chequeo de reCAPTCHA va acá (no en rules()) a propósito: una regla
     * normal de Laravel no corre si el campo no viene en el POST, y
     * justamente el caso a cubrir es un bot que ni siquiera manda ese campo
     * — with withValidator() el chequeo corre siempre, esté o no presente.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            (new Recaptcha)->validate(
                'g-recaptcha-response',
                $this->input('g-recaptcha-response'),
                fn (string $mensaje) => $validator->errors()->add('g-recaptcha-response', $mensaje),
            );
        });
    }
}
