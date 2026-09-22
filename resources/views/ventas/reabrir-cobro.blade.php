@extends('layouts.app')

@section('titulo', 'Confirmar cambio de venta #'.$pedido->numero_venta)

@section('contenido')
    <a href="{{ route('ventas.edit', $pedido) }}" class="text-sm text-slate-500 hover:underline">&larr; Volver a editar</a>

    @if ($errors->any())
        <div class="mt-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($pedido->factura?->estaAprobada())
        <div class="mt-4 rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            @if ($pedido->factura->notaCredito)
                La <strong>Factura {{ $pedido->factura->tipo_factura }}</strong> actual (CAE {{ $pedido->factura->cae }}) ya fue
                anulada con una nota de crédito, así que <strong>no se va a emitir otra</strong>. Al confirmar, se va a generar
                directamente una <strong>factura nueva</strong> con estos datos.
            @else
                Al confirmar, se va a emitir una <strong>nota de crédito</strong> por el total de la
                <strong>Factura {{ $pedido->factura->tipo_factura }}</strong> actual (CAE {{ $pedido->factura->cae }})
                y una <strong>factura nueva</strong> con estos datos.
            @endif
        </div>
    @endif

    <div class="mt-4 bg-white rounded-lg shadow overflow-hidden mb-4">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">Producto</th>
                    <th class="px-4 py-2 font-medium text-right">Cantidad</th>
                    <th class="px-4 py-2 font-medium text-right">Precio unitario</th>
                    <th class="px-4 py-2 font-medium text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($itemsResueltos as $item)
                    <tr>
                        <td class="px-4 py-2">{{ $item['nombre_producto'] }}</td>
                        <td class="px-4 py-2 text-right">{{ $item['cantidad'] }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($item['precio_unitario'], 2, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($item['subtotal'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500">Total anterior</span>
            <span>${{ number_format($pedido->total_cobrado, 2, ',', '.') }}</span>
        </div>
        <div class="flex items-center justify-between font-semibold text-lg mt-1">
            <span>Total nuevo</span>
            <span>${{ number_format($pedidoProspectivo->total, 2, ',', '.') }}</span>
        </div>
        <p class="text-sm mt-2 {{ $pedidoProspectivo->total > $pedido->total_cobrado ? 'text-red-600' : 'text-emerald-600' }}">
            {{ $pedidoProspectivo->total > $pedido->total_cobrado ? 'Hay que cobrar' : 'Hay que devolver' }}
            ${{ number_format(abs($pedidoProspectivo->total - $pedido->total_cobrado), 2, ',', '.') }} de diferencia.
        </p>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <p class="font-medium mb-3">Cómo se cubre la diferencia</p>

        <form method="POST" action="{{ route('ventas.confirmarReapertura', $pedido) }}">
            @csrf

            @foreach ($itemsPropuestos as $indice => $item)
                <input type="hidden" name="items[{{ $indice }}][producto_id]" value="{{ $item['producto_id'] }}">
                <input type="hidden" name="items[{{ $indice }}][cantidad]" value="{{ $item['cantidad'] }}">
                @if (! empty($item['precio_unitario']))
                    <input type="hidden" name="items[{{ $indice }}][precio_unitario]" value="{{ $item['precio_unitario'] }}">
                @endif
            @endforeach
            <input type="hidden" name="motivo" value="{{ $motivo }}">

            @include('partials.formulario-cobro', [
                'pedido' => $pedidoProspectivo,
                'empresa' => $empresa,
                'clientes' => collect(),
                'soloMedioPago' => true,
            ])

            <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                Confirmar y guardar
            </button>
        </form>
    </div>
@endsection
