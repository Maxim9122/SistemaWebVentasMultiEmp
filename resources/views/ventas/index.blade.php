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

            {{-- Vendedor/cajero van en una fila propia abajo (ver después de
            este form) para no amontonar todos los filtros juntos — pero
            viajan como inputs ocultos acá así "Buscar" los sigue incluyendo. --}}
            <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">
            <input type="hidden" name="cajero_id" value="{{ $cajeroId }}">

            <button type="submit" class="inline-flex items-center gap-1.5 rounded px-3 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m20 20-3.5-3.5"/>
                </svg>
                Buscar
            </button>
            @if ($numero !== '' || $fechaFiltradaManualmente || $vendedorId || $cajeroId)
                <a href="{{ route('ventas.index', ['por_pagina' => $porPagina]) }}" class="text-sm text-slate-500 hover:underline pb-2">
                    Limpiar
                </a>
            @endif
        </form>

        <div class="flex items-center gap-3">
            <a href="{{ route('ventas.exportarPdf', ['numero' => $numero, 'fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta, 'vendedor_id' => $vendedorId, 'cajero_id' => $cajeroId]) }}" target="_blank"
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
                <input type="hidden" name="vendedor_id" value="{{ $vendedorId }}">
                <input type="hidden" name="cajero_id" value="{{ $cajeroId }}">
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

    {{-- Fila propia para vendedor/cajero, separada de la de arriba a pedido
    (fecha/N° son los filtros que más se usan, estos quedan abajo). --}}
    <form method="GET" action="{{ route('ventas.index') }}" class="mb-4 flex flex-wrap items-end gap-2">
        <input type="hidden" name="numero" value="{{ $numero }}">
        <input type="hidden" name="fecha_desde" value="{{ $fechaDesde }}">
        <input type="hidden" name="fecha_hasta" value="{{ $fechaHasta }}">
        <div>
            <label for="vendedor_id" class="block text-xs text-slate-500 mb-1">Vendedor</label>
            <select id="vendedor_id" name="vendedor_id" onchange="this.form.submit()"
                class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                <option value="">Todos</option>
                @foreach ($usuariosParaFiltro as $usuario)
                    <option value="{{ $usuario->id }}" @selected($vendedorId === $usuario->id)>{{ $usuario->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="cajero_id" class="block text-xs text-slate-500 mb-1">Cajero</label>
            <select id="cajero_id" name="cajero_id" onchange="this.form.submit()"
                class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                <option value="">Todos</option>
                @foreach ($usuariosParaFiltro as $usuario)
                    <option value="{{ $usuario->id }}" @selected($cajeroId === $usuario->id)>{{ $usuario->name }}</option>
                @endforeach
            </select>
        </div>
    </form>

    @if ($totales)
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <p class="text-sm text-slate-500 mb-3">Recaudación del período filtrado</p>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                <div>
                    <p class="text-slate-500">Efectivo</p>
                    <p class="font-medium">${{ number_format($totales['efectivo'], 2, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Tarjeta</p>
                    <p class="font-medium">${{ number_format($totales['tarjeta'], 2, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Transferencia</p>
                    <p class="font-medium">${{ number_format($totales['transferencia'], 2, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Mercado Pago</p>
                    <p class="font-medium">${{ number_format($totales['mercadopago'], 2, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Total general</p>
                    <p class="font-semibold text-lg">${{ number_format($totales['general'], 2, ',', '.') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
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
                            @if ($venta->estaAnulado())
                                <span class="text-xs font-medium rounded px-1.5 py-0.5 bg-red-100 text-red-800">Anulado</span>
                            @endif
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
    </div>

    <div class="mt-4">
        {{ $ventas->links() }}
    </div>
@endsection
