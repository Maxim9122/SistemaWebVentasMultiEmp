@extends('layouts.app')

@section('titulo', 'Historial de cajas')

@section('contenido')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('cajas.index') }}" class="flex flex-wrap items-end gap-2">
            <div>
                <label for="buscar" class="block text-xs text-slate-500 mb-1">Cajero</label>
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
                <a href="{{ route('cajas.index', ['por_pagina' => $porPagina]) }}" class="text-sm text-slate-500 hover:underline pb-2">
                    Limpiar
                </a>
            @endif
        </form>

        <form method="GET" action="{{ route('cajas.index') }}" class="flex items-center gap-2 text-sm">
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

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">Cajero</th>
                    <th class="px-4 py-2 font-medium">Abierta</th>
                    <th class="px-4 py-2 font-medium">Cerrada</th>
                    <th class="px-4 py-2 font-medium text-right">Fondo</th>
                    <th class="px-4 py-2 font-medium text-right">Declarado</th>
                    <th class="px-4 py-2 font-medium text-right">Diferencia</th>
                    <th class="px-4 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($cajas as $caja)
                    <tr>
                        <td class="px-4 py-2 font-medium">{{ $caja->user->name }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $caja->abierta_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2 text-slate-500">
                            @if ($caja->estaAbierta())
                                <span class="text-xs font-medium rounded px-2 py-1 bg-emerald-100 text-emerald-800">Abierta</span>
                            @else
                                {{ $caja->cerrada_at->format('d/m/Y H:i') }}
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">${{ number_format($caja->monto_apertura, 2, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right">{{ $caja->monto_cierre_declarado !== null ? '$'.number_format($caja->monto_cierre_declarado, 2, ',', '.') : '—' }}</td>
                        <td class="px-4 py-2 text-right font-medium @if ($caja->diferencia() !== null) @class(['text-emerald-600' => $caja->diferencia() >= 0, 'text-red-600' => $caja->diferencia() < 0]) @endif">
                            @if ($caja->diferencia() !== null)
                                {{ $caja->diferencia() >= 0 ? '+' : '-' }}${{ number_format(abs($caja->diferencia()), 2, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('cajas.show', $caja) }}" class="text-sm text-slate-600 hover:underline">Ver detalle</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-slate-500">No hay cajas que coincidan con la búsqueda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $cajas->links() }}
    </div>
@endsection
