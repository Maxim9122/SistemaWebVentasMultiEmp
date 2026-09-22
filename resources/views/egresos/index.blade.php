@extends('layouts.app')

@section('titulo', 'Egresos')

@section('contenido')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('egresos.index') }}" class="flex flex-wrap items-end gap-2">
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
            @if ($fechaFiltradaManualmente)
                <a href="{{ route('egresos.index', ['por_pagina' => $porPagina]) }}" class="text-sm text-slate-500 hover:underline pb-2">
                    Limpiar
                </a>
            @endif
        </form>

        <div class="flex items-center gap-3">
            <a href="{{ route('egresos.motivos.index') }}" class="text-sm text-slate-600 hover:underline">
                Configurar motivos
            </a>
            <form method="GET" action="{{ route('egresos.index') }}" class="flex items-center gap-2 text-sm">
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
                    <th class="px-4 py-2 font-medium">Fecha</th>
                    <th class="px-4 py-2 font-medium">Motivo</th>
                    <th class="px-4 py-2 font-medium">Beneficiario</th>
                    <th class="px-4 py-2 font-medium">Cargado por</th>
                    <th class="px-4 py-2 font-medium text-right">Efectivo</th>
                    <th class="px-4 py-2 font-medium text-right">Transferencia</th>
                    <th class="px-4 py-2 font-medium">Descripción</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($egresos as $egreso)
                    <tr>
                        <td class="px-4 py-2 text-slate-500">{{ $egreso->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2">{{ $egreso->motivo->nombre }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $egreso->beneficiarioLegible() ?? '—' }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $egreso->usuario->name }}</td>
                        <td class="px-4 py-2 text-right">{{ $egreso->monto_efectivo > 0 ? '$'.number_format($egreso->monto_efectivo, 2, ',', '.') : '—' }}</td>
                        <td class="px-4 py-2 text-right">{{ $egreso->monto_transferencia > 0 ? '$'.number_format($egreso->monto_transferencia, 2, ',', '.') : '—' }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $egreso->descripcion ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-slate-500">No hay egresos que coincidan con la búsqueda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $egresos->links() }}
    </div>
@endsection
