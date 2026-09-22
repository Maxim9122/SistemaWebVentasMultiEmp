<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgregarItemCarritoRequest extends FormRequest
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
        $reglas = [
            'cantidad' => ['required', 'integer', 'min:1'],
            'precio_unitario' => ['nullable', 'numeric', 'min:0'],
        ];

        // Al actualizar una línea existente (ruta con {item}) no se cambia de producto.
        if ($this->route('item') === null) {
            $reglas['producto_id'] = [
                'required',
                'integer',
                Rule::exists('productos', 'id')
                    ->where('empresa_id', $this->user()->empresa_id)
                    ->where('activo', true),
            ];
        }

        return $reglas;
    }
}
