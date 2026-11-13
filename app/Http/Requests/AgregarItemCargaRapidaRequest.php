<?php

namespace App\Http\Requests;

use App\Models\Producto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Agregar al carrito" rápido desde la lista de Productos — mismas reglas
 * que AgregarItemCarritoRequest (ver ahí), más el campo "tipo" para elegir
 * si el producto va a un carrito (venta) o a un presupuesto.
 */
class AgregarItemCargaRapidaRequest extends FormRequest
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
        $esPorPeso = $this->productoDeLaRequest()?->esPorPeso() ?? false;

        return [
            'producto_id' => [
                'required',
                'integer',
                Rule::exists('productos', 'id')
                    ->where('empresa_id', $this->user()->empresa_id)
                    ->where('activo', true),
            ],
            'tipo' => ['required', 'string', 'in:venta,presupuesto'],
            'cantidad' => $esPorPeso ? ['nullable', 'integer', 'min:1'] : ['required', 'integer', 'min:1'],
            'precio_unitario' => $esPorPeso ? ['required', 'numeric', 'min:0.01'] : ['nullable', 'numeric', 'min:0'],
        ];
    }

    private function productoDeLaRequest(): ?Producto
    {
        if (! $this->filled('producto_id')) {
            return null;
        }

        return Producto::where('empresa_id', $this->user()->empresa_id)->find($this->input('producto_id'));
    }
}
