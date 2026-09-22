<?php

namespace App\Http\Requests\Concerns;

use App\Models\Pedido;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Reglas de validación del cobro (montos por medio de pago + factura/cliente),
 * compartidas entre `RegistrarCobroRequest` (Caja) y `CerrarCarritoRequest`
 * (atajo de cajero_vendedor, que cobra sin pasar por Caja) — ambos flujos
 * terminan en el mismo `ProcesadorDeCobro`, así que validan lo mismo.
 */
trait ValidaCobro
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function reglasCobro(): array
    {
        return [
            'monto_efectivo' => ['nullable', 'numeric', 'min:0'],
            'monto_tarjeta' => ['nullable', 'numeric', 'min:0'],
            'monto_transferencia' => ['nullable', 'numeric', 'min:0'],
            'monto_fiado' => ['nullable', 'numeric', 'min:0'],
            'tipo_comprobante' => ['required', Rule::in(Pedido::TIPOS_COMPROBANTE)],
            'cliente_id' => [
                'nullable',
                Rule::exists('clientes', 'id')->where('empresa_id', $this->user()->empresa_id),
            ],
            'cliente_nombre' => ['nullable', 'string', 'max:255'],
            'cliente_cuit' => ['nullable', 'string', 'max:20'],
            'cliente_telefono' => ['nullable', 'string', 'max:50'],
            'tipo_factura' => ['nullable', Rule::in(Pedido::TIPOS_FACTURA)],
        ];
    }

    protected function validarCobroDespues(Validator $validator, ?Pedido $pedido): void
    {
        if (! $pedido) {
            return;
        }

        $montoFiado = (float) ($this->input('monto_fiado') ?: 0);

        $montos = [
            (float) ($this->input('monto_efectivo') ?: 0),
            (float) ($this->input('monto_tarjeta') ?: 0),
            (float) ($this->input('monto_transferencia') ?: 0),
            $montoFiado,
        ];

        $suma = round(array_sum($montos), 2);
        $total = round((float) $pedido->total, 2);

        if ($suma < $total) {
            $validator->errors()->add('monto_efectivo', 'Falta cubrir $'.number_format($total - $suma, 2, ',', '.').' del total.');
        } elseif ($suma > $total) {
            $validator->errors()->add('monto_efectivo', 'Te pasaste por $'.number_format($suma - $total, 2, ',', '.').' del total.');
        }

        $tieneClienteExistente = filled($this->input('cliente_id'));
        $tieneClienteNuevo = filled($this->input('cliente_nombre')) && filled($this->input('cliente_cuit'));

        $empresa = $this->user()->empresa;

        // El fiado no depende de si la venta es factura o remito — de
        // cualquier forma hace falta saber quién debe la plata.
        if ($montoFiado > 0) {
            if (! $empresa->permite_fiado) {
                $validator->errors()->add('monto_fiado', 'Esta empresa no tiene habilitada la venta a crédito (fiado).');
            }

            if (! $tieneClienteExistente && ! $tieneClienteNuevo) {
                $validator->errors()->add('cliente_nombre', 'Para fiar una venta, elegí o cargá un cliente.');
            }
        }

        if ($this->input('tipo_comprobante') !== 'factura') {
            return;
        }

        // Factura A va siempre a nombre de un CUIT real (lo exige AFIP: la
        // condición de IVA del receptor tiene que estar identificada). Para B/C
        // el cliente es opcional — sin datos, la factura sale a Consumidor Final.
        if ($this->input('tipo_factura') === 'A' && ! $tieneClienteExistente && ! $tieneClienteNuevo) {
            $validator->errors()->add('cliente_nombre', 'La Factura A necesita un cliente con CUIT.');
        }

        if (! $empresa->puedeFacturar()) {
            $validator->errors()->add('tipo_comprobante', 'Configurá la condición fiscal de tu empresa antes de facturar.');

            return;
        }

        if (! $empresa->factura_habilitada) {
            $validator->errors()->add('tipo_comprobante', 'La facturación está desactivada temporalmente. Un admin puede reactivarla en Configuración.');

            return;
        }

        if (blank($this->input('tipo_factura'))) {
            $validator->errors()->add('tipo_factura', 'Elegí el tipo de factura.');
        } elseif ($empresa->esResponsableInscripto() && ! in_array($this->input('tipo_factura'), ['A', 'B'], true)) {
            $validator->errors()->add('tipo_factura', 'Tu empresa es Responsable Inscripto: elegí factura A o B.');
        } elseif (! $empresa->esResponsableInscripto() && $this->input('tipo_factura') !== 'C') {
            $validator->errors()->add('tipo_factura', 'Tu empresa es Monotributista: la factura tiene que ser C.');
        }
    }
}
