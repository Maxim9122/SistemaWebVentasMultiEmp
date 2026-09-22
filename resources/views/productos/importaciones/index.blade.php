@extends('layouts.app')

@section('titulo', 'Importaciones')

@section('contenido')
    <div class="mb-4">
        <a href="{{ route('productos.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Volver a Productos</a>
    </div>

    <div class="bg-white rounded-lg shadow divide-y">
        @forelse ($importaciones as $importacion)
            <div class="p-4 flex items-center justify-between gap-4">
                <div>
                    <p class="font-medium">{{ $importacion->nombre_archivo }}</p>
                    <p class="text-sm text-slate-500">
                        {{ $importacion->created_at->format('d/m/Y H:i') }} ·
                        {{ $importacion->usuario->name ?? '—' }} ·
                        proveedor: {{ $importacion->proveedor->nombre ?? 'ninguno' }}
                    </p>
                    <p class="text-sm text-slate-500">
                        {{ $importacion->creados_count }} creados · {{ $importacion->actualizados_count }} actualizados ·
                        {{ $importacion->errores_count }} errores
                    </p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    @if ($importacion->fueRevertida())
                        <span class="text-xs font-medium rounded px-2 py-1 bg-slate-200 text-slate-700">
                            Revertida el {{ $importacion->revertida_at->format('d/m/Y H:i') }}
                        </span>
                    @else
                        <form method="POST" action="{{ route('productos.importaciones.deshacer', $importacion) }}"
                            onsubmit="return confirm('¿Deshacer esta importación? Los productos creados se van a borrar y los actualizados van a volver a su estado anterior. Si editaste algo a mano después de importar, ese cambio también se va a perder. Esta acción no se puede deshacer.');">
                            @csrf
                            <button type="submit" class="text-sm text-red-600 hover:underline">Deshacer</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="p-4 text-sm text-slate-500">Todavía no se hizo ninguna importación.</p>
        @endforelse
    </div>
@endsection
