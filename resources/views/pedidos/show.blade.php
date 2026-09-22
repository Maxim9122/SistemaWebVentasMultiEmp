@extends('layouts.app')

@section('titulo', 'Pedido programado')

@section('contenido')
    <a href="{{ route('pedidos.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Volver a Pedidos</a>

    <div class="mt-4 bg-white rounded-lg shadow overflow-hidden mb-4">
        <div class="px-4 py-3 border-b bg-slate-50 flex items-center justify-between">
            <div>
                <p class="font-medium">{{ $pedido->cliente_nombre }}</p>
                <p class="text-sm text-slate-500">
                    Para el {{ $pedido->fecha_programada->format('d/m/Y') }} · Armado por {{ $pedido->vendedor->name }}
                </p>
            </div>
            <a href="{{ route('pedidos.editar', $pedido) }}" class="text-sm text-slate-600 hover:underline">
                Editar pedido
            </a>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">Producto</th>
                    <th class="px-4 py-2 font-medium text-right">Cantidad</th>
                    <th class="px-4 py-2 font-medium text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($pedido->items as $item)
                    <tr>
                        <td class="px-4 py-2">{{ $item->nombre_producto }}</td>
                        <td class="px-4 py-2 text-right">{{ $item->cantidad }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($item->subtotal, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-lg shadow p-4 flex items-center justify-between">
        <p class="text-lg font-semibold">Total: ${{ number_format($pedido->total, 2, ',', '.') }}</p>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('pedidos.pasarACaja', $pedido) }}">
                @csrf
                <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Pasar a caja
                </button>
            </form>
            <form method="POST" action="{{ route('pedidos.cancelar', $pedido) }}">
                @csrf
                <button type="submit" class="rounded px-4 py-2 text-sm font-medium text-red-600 hover:underline">
                    Cancelar
                </button>
            </form>
        </div>
    </div>
@endsection
