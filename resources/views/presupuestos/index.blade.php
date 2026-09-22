@extends('layouts.app')

@section('titulo', 'Presupuestos')

@section('contenido')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('presupuestos.index') }}" class="flex flex-wrap items-end gap-2">
            <div>
                <label for="numero" class="block text-xs text-slate-500 mb-1">N° de presupuesto</label>
                <input type="number" id="numero" name="numero" value="{{ $numero }}" min="1"
                    class="w-32 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
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
            <button type="submit" class="rounded px-3 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                Buscar
            </button>
            @if ($numero !== '' || $fechaFiltradaManualmente)
                <a href="{{ route('presupuestos.index', ['por_pagina' => $porPagina]) }}" class="text-sm text-slate-500 hover:underline pb-2">
                    Limpiar
                </a>
            @endif
        </form>

        <div class="flex items-center gap-3">
            <form method="POST" action="{{ route('presupuestos.store') }}">
                @csrf
                <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Nuevo presupuesto
                </button>
            </form>
            <form method="GET" action="{{ route('presupuestos.index') }}" class="flex items-center gap-2 text-sm">
                <input type="hidden" name="numero" value="{{ $numero }}">
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
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">N°</th>
                    <th class="px-4 py-2 font-medium">Fecha</th>
                    <th class="px-4 py-2 font-medium">Cliente</th>
                    <th class="px-4 py-2 font-medium">Armado por</th>
                    <th class="px-4 py-2 font-medium text-right">Total</th>
                    <th class="px-4 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($presupuestos as $presupuesto)
                    <tr>
                        <td class="px-4 py-2 font-medium">{{ $presupuesto->numero_presupuesto }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $presupuesto->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2">{{ $presupuesto->cliente?->nombre ?? $presupuesto->cliente_nombre }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $presupuesto->vendedor->name }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($presupuesto->total, 2, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('presupuestos.show', $presupuesto) }}" class="text-sm text-slate-600 hover:underline">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">No hay presupuestos que coincidan con la búsqueda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $presupuestos->links() }}
    </div>
@endsection
