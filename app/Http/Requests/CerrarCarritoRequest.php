<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaCobro;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CerrarCarritoRequest extends FormRequest
{
    use ValidaCobro;

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
        $reglas = [
            'destino' => ['required', Rule::in(['inmediato', 'programado'])],
            'fecha_programada' => ['nullable', 'date', 'after_or_equal:today', 'required_if:destino,programado'],
        ];

        if ($this->cobraAlToque()) {
            $reglas = [...$reglas, ...$this->reglasCobro()];
        }

        return $reglas;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $pedido = $this->route('pedido');

            if ($pedido && $pedido->items()->count() === 0) {
                $validator->errors()->add('destino', 'El carrito está vacío, agregá al menos un producto antes de cerrarlo.');
            }

            if ($this->cobraAlToque()) {
                $this->validarCobroDespues($validator, $pedido);
            }
        });
    }

    private function cobraAlToque(): bool
    {
        return $this->user()->role === 'cajero_vendedor' && $this->input('destino') === 'inmediato';
    }
}
