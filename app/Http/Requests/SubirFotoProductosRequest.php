<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubirFotoProductosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 8MB (no 2MB como el logo de empresa) — una foto de celular sin
            // comprimir no entra en el límite que se usa para esa imagen chica.
            'foto' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
            'proveedor_id' => [
                'nullable',
                Rule::exists('proveedores', 'id')->where('empresa_id', $this->user()->empresa_id),
            ],
        ];
    }
}
