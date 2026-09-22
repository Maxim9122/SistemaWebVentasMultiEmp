@extends('layouts.app')

@section('titulo', 'Grupos de productos')

@section('contenido')
    <a href="{{ route('productos.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Volver a Productos</a>

    <div class="mt-4 bg-white rounded-lg shadow divide-y">
        @forelse ($grupos as $grupo)
            <a href="{{ route('productos.grupos.show', $grupo) }}" class="p-4 flex items-center justify-between hover:bg-slate-50">
                <p class="font-medium">{{ $grupo->nombre }}</p>
                <p class="text-sm text-slate-500">{{ $grupo->productos_count }} producto{{ $grupo->productos_count === 1 ? '' : 's' }}</p>
            </a>
        @empty
            <p class="p-4 text-sm text-slate-500">
                Todavía no armaste ningún grupo. Desde <a href="{{ route('productos.index') }}" class="underline">Productos</a>,
                tildá algunos y creá tu primer grupo.
            </p>
        @endforelse
    </div>
@endsection
