@extends('layouts.app')

@section('titulo', 'Proveedores')

@section('contenido')
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('productos.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Volver a Productos</a>
        <a href="{{ route('productos.proveedores.create') }}" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
            + Nuevo proveedor
        </a>
    </div>

    <div class="bg-white rounded-lg shadow divide-y">
        @forelse ($proveedores as $proveedor)
            <div class="p-4 flex items-center justify-between">
                <div>
                    <a href="{{ route('productos.proveedores.edit', $proveedor) }}" class="font-medium hover:underline">{{ $proveedor->nombre }}</a>
                    <p class="text-sm text-slate-500">{{ $proveedor->telefono ?? '—' }} · {{ $proveedor->email ?? '—' }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-medium rounded px-2 py-1 @class(['bg-emerald-100 text-emerald-800' => $proveedor->activo, 'bg-slate-200 text-slate-700' => ! $proveedor->activo])">
                        {{ $proveedor->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                    @if ($proveedor->activo)
                        <form method="POST" action="{{ route('productos.proveedores.desactivar', $proveedor) }}">
                            @csrf
                            <button type="submit" class="text-sm text-red-600 hover:underline">Desactivar</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('productos.proveedores.activar', $proveedor) }}">
                            @csrf
                            <button type="submit" class="text-sm text-emerald-600 hover:underline">Activar</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="p-4 text-sm text-slate-500">Todavía no cargaste ningún proveedor.</p>
        @endforelse
    </div>
@endsection
