<?php

namespace App\Http\Requests;

use App\Models\Producto;
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
        $esPorPeso = $this->productoDeLaRequest()?->esPorPeso() ?? false;

        // Un producto por peso no pide cantidad (siempre es 1 línea = el
        // precio total que ya pesó y calculó la balanza) — en cambio ese
        // precio pasa a ser obligatorio, no una excepción "especial" como
        // para el resto de los productos.
        $reglas = [
            'cantidad' => $esPorPeso ? ['nullable', 'integer', 'min:1'] : ['required', 'integer', 'min:1'],
            'precio_unitario' => $esPorPeso ? ['required', 'numeric', 'min:0.01'] : ['nullable', 'numeric', 'min:0'],
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

    private function productoDeLaRequest(): ?Producto
    {
        $item = $this->route('item');

        if ($item !== null) {
            return $item->producto;
        }

        if (! $this->filled('producto_id')) {
            return null;
        }

        return Producto::where('empresa_id', $this->user()->empresa_id)->find($this->input('producto_id'));
    }
}
