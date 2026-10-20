<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ConfirmarExtraccionFotoClientesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mismo motivo que ConfirmarExtraccionFotoProductosRequest: un único
     * campo `clientes_json` (JSON.stringify armado en JS) en vez de inputs
     * indexados, para no arriesgarse a max_input_vars con una lista larga.
     */
    protected function prepareForValidation(): void
    {
        $decodificado = json_decode((string) $this->input('clientes_json', '[]'), true);

        $this->merge(['clientes' => is_array($decodificado) ? $decodificado : []]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'clientes' => ['required', 'array', 'min:1'],
            // Reglas livianas a propósito — la limpieza real no hace falta
            // acá (a diferencia de productos, estos campos ya son todos
            // texto libre): el flujo real de ClienteImportController::confirmar()
            // ya valida/normaliza lo que haga falta cuando el archivo
            // generado pase por ahí.
            'clientes.*.nombre' => ['nullable', 'string', 'max:255'],
            'clientes.*.cuit' => ['nullable', 'string', 'max:50'],
            'clientes.*.telefono' => ['nullable', 'string', 'max:50'],
            'clientes.*.email' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hayAlgunaFilaConDatos = collect($this->input('clientes', []))->contains(
                fn (array $fila) => collect($fila)->contains(fn ($valor) => trim((string) $valor) !== '')
            );

            if (! $hayAlgunaFilaConDatos) {
                $validator->errors()->add('clientes', 'No hay ningún cliente cargado para importar.');
            }
        });
    }
}
