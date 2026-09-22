@extends('layouts.app')

@section('titulo', 'Historial de sesiones')

@section('contenido')
    <div class="mb-4">
        <a href="{{ route('staff.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Volver a Staff</a>
    </div>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('staff.sesiones') }}" class="flex flex-wrap items-end gap-2">
            <div>
                <label for="buscar" class="block text-xs text-slate-500 mb-1">Usuario</label>
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
            <button type="submit" class="rounded px-3 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                Buscar
            </button>
            @if ($buscar !== '' || $fechaFiltradaManualmente)
                <a href="{{ route('staff.sesiones', ['por_pagina' => $porPagina]) }}" class="text-sm text-slate-500 hover:underline pb-2">
                    Limpiar
                </a>
            @endif
        </form>

        <form method="GET" action="{{ route('staff.sesiones') }}" class="flex items-center gap-2 text-sm">
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
                    <th class="px-4 py-2 font-medium">Usuario</th>
                    <th class="px-4 py-2 font-medium">Rol</th>
                    <th class="px-4 py-2 font-medium">Ingreso</th>
                    <th class="px-4 py-2 font-medium">Cierre</th>
                    <th class="px-4 py-2 font-medium">Duración</th>
                    <th class="px-4 py-2 font-medium">IP</th>
                    <th class="px-4 py-2 font-medium">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($sesiones as $sesion)
                    <tr class="{{ $sesion->bloqueada ? 'bg-red-50' : '' }}">
                        <td class="px-4 py-2 font-medium">{{ $sesion->user->name }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ str_replace('_', ' ', $sesion->user->role) }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $sesion->login_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2 text-slate-500">
                            @if ($sesion->bloqueada)
                                —
                            @elseif ($sesion->logout_at)
                                {{ $sesion->logout_at->format('d/m/Y H:i') }}
                            @else
                                <span class="text-xs font-medium rounded px-2 py-1 bg-amber-100 text-amber-800">Sin cierre registrado</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-slate-500">{{ $sesion->bloqueada ? '—' : ($sesion->duracionLegible() ?? '—') }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $sesion->ip_address ?? '—' }}</td>
                        <td class="px-4 py-2">
                            @if ($sesion->bloqueada)
                                <span class="text-xs font-medium rounded px-2 py-1 bg-red-100 text-red-800" title="{{ $sesion->motivo_bloqueo }}">
                                    Acceso rechazado
                                </span>
                            @else
                                <span class="text-xs font-medium rounded px-2 py-1 bg-emerald-100 text-emerald-800">Ingreso válido</span>
                            @endif
                        </td>
                    </tr>
                    @if ($sesion->bloqueada)
                        <tr class="bg-red-50">
                            <td colspan="7" class="px-4 pb-2 -mt-2 text-xs text-red-700">{{ $sesion->motivo_bloqueo }}</td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-slate-500">No hay sesiones que coincidan con la búsqueda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $sesiones->links() }}
    </div>
@endsection
