<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarProductoRequest extends FormRequest
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
        $producto = $this->route('producto');

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'codigo' => [
                'nullable', 'string', 'max:255',
                Rule::unique('productos', 'codigo')
                    ->where('empresa_id', $this->user()->empresa_id)
                    ->ignore($producto?->id),
            ],
            'precio' => ['required', 'numeric', 'min:0'],
            'costo' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:255'],
            'unidad' => ['nullable', 'string', 'max:255'],
            'proveedor_id' => [
                'nullable',
                Rule::exists('proveedores', 'id')->where('empresa_id', $this->user()->empresa_id),
            ],
            'cantidad_minima_1' => ['nullable', 'integer', 'min:2', 'required_with:precio_cantidad_1'],
            'precio_cantidad_1' => ['nullable', 'numeric', 'min:0', 'required_with:cantidad_minima_1'],
            'cantidad_minima_2' => ['nullable', 'integer', 'min:2', 'required_with:precio_cantidad_2'],
            'precio_cantidad_2' => ['nullable', 'numeric', 'min:0', 'required_with:cantidad_minima_2'],
            'cantidad_minima_3' => ['nullable', 'integer', 'min:2', 'required_with:precio_cantidad_3'],
            'precio_cantidad_3' => ['nullable', 'numeric', 'min:0', 'required_with:cantidad_minima_3'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $anterior = null;

            foreach ([1, 2, 3] as $n) {
                $valor = $this->input("cantidad_minima_{$n}");

                if ($valor === null || $valor === '') {
                    continue;
                }

                if ($anterior !== null && (int) $valor <= $anterior) {
                    $validator->errors()->add(
                        "cantidad_minima_{$n}",
                        "El tramo {$n} tiene que ser una cantidad mayor a la del tramo anterior."
                    );
                }

                $anterior = (int) $valor;
            }
        });
    }
}
