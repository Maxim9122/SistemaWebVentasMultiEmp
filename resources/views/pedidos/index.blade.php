@extends('layouts.app')

@section('titulo', 'Pedidos programados')

@section('contenido')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('pedidos.index') }}" class="flex flex-wrap items-end gap-2">
            <div>
                <label for="buscar" class="block text-xs text-slate-500 mb-1">Cliente</label>
                <input type="text" id="buscar" name="buscar" value="{{ $buscar }}" placeholder="Nombre..."
                    class="w-40 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            </div>
            <div>
                <label for="fecha_desde" class="block text-xs text-slate-500 mb-1">Desde</label>
                <input type="date" id="fecha_desde" name="fecha_desde" value="{{ $fechaDesde }}"
                    class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            </div>
            <div>
                <label for="fecha_hasta" class="block text-xs text-slate-500 mb-1">Hasta</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta" value="{{ $fechaHasta }}"
                    class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            </div>
            <button type="submit" class="inline-flex items-center gap-1.5 rounded px-3 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m20 20-3.5-3.5"/>
                </svg>
                Buscar
            </button>
            @if ($buscar !== '' || $fechaFiltradaManualmente)
                <a href="{{ route('pedidos.index', ['por_pagina' => $porPagina]) }}" class="text-sm text-slate-500 hover:underline pb-2">
                    Limpiar
                </a>
            @endif
        </form>

        <form method="GET" action="{{ route('pedidos.index') }}" class="flex items-center gap-2 text-sm">
            <input type="hidden" name="buscar" value="{{ $buscar }}">
            <input type="hidden" name="fecha_desde" value="{{ $fechaDesde }}">
            <input type="hidden" name="fecha_hasta" value="{{ $fechaHasta }}">
            <label for="por_pagina" class="text-slate-500">Mostrar</label>
            <select id="por_pagina" name="por_pagina" onchange="this.form.submit()"
                class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                <option value="10" @selected($porPagina === 10)>10</option>
                <option value="50" @selected($porPagina === 50)>50</option>
                <option value="100" @selected($porPagina === 100)>100</option>
            </select>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow divide-y">
        @forelse ($pedidos as $pedido)
            <div class="p-4 flex items-center justify-between">
                <div>
                    <a href="{{ route('pedidos.show', $pedido) }}" class="font-medium hover:underline">{{ $pedido->cliente_nombre }}</a>
                    <p class="text-sm text-slate-500">
                        Para el {{ $pedido->fecha_programada->format('d/m/Y') }} · Armado por {{ $pedido->vendedor->name }}
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <p class="font-medium">${{ number_format($pedido->total, 2, ',', '.') }}</p>
                    <form method="POST" action="{{ route('pedidos.pasarACaja', $pedido) }}">
                        @csrf
                        <button type="submit" class="text-sm text-slate-600 hover:underline">Pasar a caja</button>
                    </form>
                    <form method="POST" action="{{ route('pedidos.cancelar', $pedido) }}">
                        @csrf
                        <button type="submit" class="text-sm text-red-600 hover:underline">Cancelar</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="p-4 text-sm text-slate-500">No hay pedidos programados que coincidan con la búsqueda.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $pedidos->links() }}
    </div>
@endsection
