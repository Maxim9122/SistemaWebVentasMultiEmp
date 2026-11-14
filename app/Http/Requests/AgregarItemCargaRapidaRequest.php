<?php

namespace App\Http\Requests;

use App\Models\Producto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * "Agregar al carrito/presupuesto" rápido desde la lista de Productos — un
 * solo producto (botón de una fila) o varios de una (botón de una fila ya
 * tildada junto con otras, ver btn-carga-rapida en la vista): siempre se
 * manda como una lista de 1 o más, para no tener dos formas distintas de
 * validar lo mismo.
 *
 * Tope de 30 productos por el mismo motivo que ProductoController::porIds():
 * este modal pide cantidad/precio fila por fila a mano, no tiene sentido
 * para una selección de cientos de productos.
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
        return [
            'tipo' => ['required', 'string', 'in:venta,presupuesto'],
            'productos' => ['required', 'array', 'min:1', 'max:30'],
            'productos.*.producto_id' => [
                'required',
                'integer',
                Rule::exists('productos', 'id')
                    ->where('empresa_id', $this->user()->empresa_id)
                    ->where('activo', true),
            ],
            'productos.*.cantidad' => ['required', 'integer', 'min:1'],
            'productos.*.precio_unitario' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $productosEnviados = $this->input('productos', []);

            if (! is_array($productosEnviados)) {
                return;
            }

            $idsValidos = array_filter(array_map(
                fn ($item) => is_array($item) && is_numeric($item['producto_id'] ?? null) ? (int) $item['producto_id'] : null,
                $productosEnviados,
            ));

            if ($idsValidos === []) {
                return;
            }

            // Un producto por peso no tiene "precio de catálogo" aplicable
            // (ver ProductoCargaRapidaController) — el precio es siempre el
            // que se cargó a mano, así que acá es obligatorio, no una
            // excepción opcional como para el resto.
            $productosPorPeso = Producto::where('empresa_id', $this->user()->empresa_id)
                ->whereIn('id', $idsValidos)
                ->get()
                ->filter(fn (Producto $producto) => $producto->esPorPeso())
                ->keyBy('id');

            foreach ($productosEnviados as $indice => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $productoId = (int) ($item['producto_id'] ?? 0);
                $producto = $productosPorPeso->get($productoId);

                if ($producto && blank($item['precio_unitario'] ?? null)) {
                    $validator->errors()->add(
                        "productos.{$indice}.precio_unitario",
                        "\"{$producto->nombre}\" es un producto por peso — necesita indicar el precio.",
                    );
                }
            }
        });
    }
}
