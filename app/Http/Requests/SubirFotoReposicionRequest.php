<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubirFotoReposicionRequest extends FormRequest
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
            'foto' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
            'proveedor_id' => [
                'nullable',
                Rule::exists('proveedores', 'id')->where('empresa_id', $this->user()->empresa_id),
            ],
        ];
    }
}
