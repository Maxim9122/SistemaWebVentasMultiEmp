@extends('layouts.app')

@section('titulo', 'Caja')

@section('contenido')
    <div class="bg-white rounded-lg shadow divide-y">
        @forelse ($pedidos as $pedido)
            <a href="{{ route('caja.show', $pedido) }}" class="p-4 flex items-center justify-between hover:bg-slate-50">
                <div>
                    <p class="font-medium">{{ $pedido->cliente_nombre }}</p>
                    <p class="text-sm text-slate-500">Armado por {{ $pedido->vendedor->name }} · {{ $pedido->created_at->format('H:i') }}</p>
                </div>
                <p class="font-medium">${{ number_format($pedido->total, 2, ',', '.') }}</p>
            </a>
        @empty
            <p class="p-4 text-sm text-slate-500">No hay pedidos esperando cobro.</p>
        @endforelse
    </div>
@endsection
