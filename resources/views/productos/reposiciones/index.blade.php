@extends('layouts.app')

@section('titulo', 'Reposiciones de stock')

@section('contenido')
    <div class="mb-4">
        <a href="{{ route('productos.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Volver a Productos</a>
    </div>

    <div class="bg-white rounded-lg shadow divide-y">
        @forelse ($reposiciones as $reposicion)
            <a href="{{ route('productos.reposiciones.show', $reposicion) }}" class="p-4 flex items-center justify-between gap-4 hover:bg-slate-50 block">
                <div>
                    <p class="font-medium">
                        {{ $reposicion->items_count }} producto(s) repuesto(s)
                        @if ($reposicion->proveedor)
                            · Proveedor: {{ $reposicion->proveedor->nombre }}
                        @endif
                    </p>
                    <p class="text-sm text-slate-500">
                        {{ $reposicion->created_at->format('d/m/Y H:i') }} · {{ $reposicion->user->name ?? '—' }}
                        @if ($reposicion->nota)
                            · {{ $reposicion->nota }}
                        @endif
                    </p>
                </div>
                <div class="shrink-0">
                    @if ($reposicion->fueRevertida())
                        <span class="text-xs font-medium rounded px-2 py-1 bg-slate-200 text-slate-700">
                            Revertida el {{ $reposicion->revertida_at->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>
            </a>
        @empty
            <p class="p-4 text-sm text-slate-500">Todavía no se repuso stock ninguna vez.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $reposiciones->links() }}
    </div>
@endsection
