@extends('layouts.app')

@section('titulo', 'Historial de ajustes de precio')

@section('contenido')
    <div class="mb-4">
        <a href="{{ route('productos.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Volver a Productos</a>
    </div>

    <div class="bg-white rounded-lg shadow divide-y">
        @forelse ($ajustes as $ajuste)
            <div class="p-4 flex items-center justify-between gap-4">
                <div>
                    <p class="font-medium">
                        {{ $ajuste->porcentaje > 0 ? '+' : '' }}{{ number_format($ajuste->porcentaje, 2, ',', '.') }}%
                        a {{ $ajuste->productos_count }} producto(s)
                    </p>
                    <p class="text-sm text-slate-500">
                        {{ $ajuste->created_at->format('d/m/Y H:i') }} ·
                        {{ $ajuste->usuario->name ?? '—' }} ·
                        filtro: {{ $ajuste->descripcion_filtro }}
                    </p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    @if ($ajuste->fueRevertido())
                        <span class="text-xs font-medium rounded px-2 py-1 bg-slate-200 text-slate-700">
                            Revertido el {{ $ajuste->revertido_at->format('d/m/Y H:i') }}
                        </span>
                    @else
                        <form method="POST" action="{{ route('productos.ajustesPrecio.deshacer', $ajuste) }}"
                            onsubmit="return confirm('¿Deshacer este ajuste de precio? Los productos que sigan con el precio que dejó este ajuste van a volver a su precio anterior. Si alguno se volvió a editar después, ese cambio no se toca. Esta acción no se puede deshacer.');">
                            @csrf
                            <button type="submit" class="text-sm text-red-600 hover:underline">Deshacer</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="p-4 text-sm text-slate-500">Todavía no se aplicó ningún ajuste de precio por búsqueda.</p>
        @endforelse
    </div>
@endsection
