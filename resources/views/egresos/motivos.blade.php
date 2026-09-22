@extends('layouts.app')

@section('titulo', 'Motivos de egreso')

@section('contenido')
    <div class="mb-4">
        <a href="{{ route('egresos.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Volver a Egresos</a>
    </div>

    <div class="max-w-lg bg-white rounded-lg shadow p-6 mb-4">
        <p class="text-sm font-medium mb-3">Agregar motivo</p>
        <form method="POST" action="{{ route('egresos.motivos.store') }}" class="space-y-3">
            @csrf
            <input type="text" name="nombre" placeholder="ej: Fletes" required
                class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">

            <div class="space-y-1 text-sm text-slate-600">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="requiere_producto" value="1" class="rounded border border-slate-300">
                    Pide producto y cantidad, descuenta stock (ej: consumos internos)
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="solo_proveedores" value="1" class="rounded border border-slate-300">
                    El buscador de beneficiario solo muestra proveedores
                </label>
            </div>

            <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                Agregar
            </button>
        </form>
    </div>

    <div class="max-w-lg bg-white rounded-lg shadow divide-y">
        @forelse ($motivos as $motivo)
            <div class="p-4 flex items-center justify-between gap-4">
                <div>
                    <span class="{{ $motivo->activo ? '' : 'text-slate-400 line-through' }}">{{ $motivo->nombre }}</span>
                    <div class="flex gap-1 mt-1">
                        @if ($motivo->requiere_producto)
                            <span class="text-xs rounded px-2 py-0.5 bg-amber-100 text-amber-800">Pide producto</span>
                        @endif
                        @if ($motivo->solo_proveedores)
                            <span class="text-xs rounded px-2 py-0.5 bg-sky-100 text-sky-800">Solo proveedores</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <form method="POST" action="{{ route('egresos.motivos.alternarRequiereProducto', $motivo) }}">
                        @csrf
                        <button type="submit" class="text-xs text-slate-500 hover:underline">
                            {{ $motivo->requiere_producto ? 'Quitar "pide producto"' : 'Marcar "pide producto"' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('egresos.motivos.alternarSoloProveedores', $motivo) }}">
                        @csrf
                        <button type="submit" class="text-xs text-slate-500 hover:underline">
                            {{ $motivo->solo_proveedores ? 'Quitar "solo proveedores"' : 'Marcar "solo proveedores"' }}
                        </button>
                    </form>
                    @if ($motivo->activo)
                        <form method="POST" action="{{ route('egresos.motivos.desactivar', $motivo) }}">
                            @csrf
                            <button type="submit" class="text-sm text-red-600 hover:underline">Desactivar</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('egresos.motivos.activar', $motivo) }}">
                            @csrf
                            <button type="submit" class="text-sm text-emerald-600 hover:underline">Activar</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="p-4 text-sm text-slate-500">Todavía no hay motivos cargados.</p>
        @endforelse
    </div>
@endsection
