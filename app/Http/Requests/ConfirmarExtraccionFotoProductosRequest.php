<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ConfirmarExtraccionFotoProductosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * La tabla editable manda un único campo `productos_json` (JSON.stringify
     * armado en JS) en vez de inputs indexados tipo `productos[0][nombre]` —
     * con max_input_vars=1000 (default de PHP), una lista de ~125+ productos
     * (8 campos por fila) ya lo superaría y PHP trunca en silencio los
     * campos que sobran, sin ningún error visible. Acá se decodifica una
     * sola vez para que las reglas de abajo validen igual que si hubiera
     * llegado como array.
     */
    protected function prepareForValidation(): void
    {
        $decodificado = json_decode((string) $this->input('productos_json', '[]'), true);

        $this->merge(['productos' => is_array($decodificado) ? $decodificado : []]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'proveedor_id' => [
                'nullable',
                Rule::exists('proveedores', 'id')->where('empresa_id', $this->user()->empresa_id),
            ],
            'productos' => ['required', 'array', 'min:1'],
            // Reglas livianas a propósito: la limpieza real de precio/costo/
            // stock (separadores de miles/decimales, símbolo de moneda) la
            // hace normalizarNumero() dentro de confirmar() cuando el
            // archivo generado pase por el flujo real — acá solo interesa
            // la forma, no duplicar esa lógica.
            'productos.*.nombre' => ['nullable', 'string', 'max:255'],
            'productos.*.codigo' => ['nullable', 'string', 'max:100'],
            'productos.*.precio' => ['nullable', 'string', 'max:50'],
            'productos.*.costo' => ['nullable', 'string', 'max:50'],
            'productos.*.stock' => ['nullable', 'string', 'max:50'],
            'productos.*.categoria' => ['nullable', 'string', 'max:255'],
            'productos.*.marca' => ['nullable', 'string', 'max:255'],
            'productos.*.unidad' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hayAlgunaFilaConDatos = collect($this->input('productos', []))->contains(
                fn (array $fila) => collect($fila)->contains(fn ($valor) => trim((string) $valor) !== '')
            );

            if (! $hayAlgunaFilaConDatos) {
                $validator->errors()->add('productos', 'No hay ningún producto cargado para importar.');
            }
        });
    }
}
