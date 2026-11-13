<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubirPdfProductosRequest extends FormRequest
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
            // 15MB (más que una foto sola) porque puede traer varias
            // páginas — la cantidad de páginas en sí la limita
            // PdfAImagenesService, no esto.
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:15360'],
            'proveedor_id' => [
                'nullable',
                Rule::exists('proveedores', 'id')->where('empresa_id', $this->user()->empresa_id),
            ],
        ];
    }
}
