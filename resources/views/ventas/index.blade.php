@extends('layouts.app')

@section('titulo', 'Ventas')

@section('contenido')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('ventas.index') }}" class="flex flex-wrap items-end gap-2">
            <div>
                <label for="numero" class="block text-xs text-slate-500 mb-1">N° de venta</label>
                <input type="number" id="numero" name="numero" value="{{ $numero }}" min="1"
                    class="w-28 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
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
            @if ($numero !== '' || $fechaFiltradaManualmente)
                <a href="{{ route('ventas.index', ['por_pagina' => $porPagina]) }}" class="text-sm text-slate-500 hover:underline pb-2">
                    Limpiar
                </a>
            @endif
        </form>

        <div class="flex items-center gap-3">
            <a href="{{ route('ventas.exportarPdf', ['numero' => $numero, 'fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta]) }}" target="_blank"
                class="inline-flex items-center gap-1.5 rounded px-3 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                    <path d="M12 4v10m0 0-3.5-3.5M12 14l3.5-3.5"/>
                    <path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>
                </svg>
                Descargar PDF
            </a>
            <a href="{{ route('ventas.reporteProducto') }}" class="text-sm text-slate-600 hover:underline">
                Ventas por producto
            </a>
            <form method="GET" action="{{ route('ventas.index') }}" class="flex items-center gap-2 text-sm">
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
                    <th class="px-4 py-2 font-medium">Comprobante</th>
                    <th class="px-4 py-2 font-medium">Vendedor</th>
                    <th class="px-4 py-2 font-medium">Cajero</th>
                    <th class="px-4 py-2 font-medium">Medio de pago</th>
                    <th class="px-4 py-2 font-medium text-right">Total cobrado</th>
                    <th class="px-4 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($ventas as $venta)
                    <tr>
                        <td class="px-4 py-2 font-medium">{{ $venta->numero_venta }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $venta->cobrado_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2">{{ $venta->factura?->cliente_nombre ?? $venta->cliente_nombre }}</td>
                        <td class="px-4 py-2 text-slate-500">
                            {{ $venta->tipo_comprobante === 'factura' ? 'Factura '.$venta->factura?->tipo_factura : 'Remito' }}
                        </td>
                        <td class="px-4 py-2 text-slate-500">{{ $venta->vendedor->name }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $venta->cajero->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ ucfirst($venta->forma_pago ?? '—') }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($venta->total_cobrado, 2, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('ventas.show', $venta) }}" class="text-sm text-slate-600 hover:underline">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-6 text-center text-slate-500">No hay ventas que coincidan con la búsqueda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $ventas->links() }}
    </div>
@endsection
