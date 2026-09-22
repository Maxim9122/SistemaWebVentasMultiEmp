@extends('layouts.app')

@section('titulo', 'Nuevo proveedor')

@section('contenido')
    <div class="max-w-lg bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('productos.proveedores.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="nombre" class="block text-sm font-medium mb-1">Nombre</label>
                <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" required
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="telefono" class="block text-sm font-medium mb-1">Teléfono</label>
                    <input id="telefono" name="telefono" type="text" value="{{ old('telefono') }}"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium mb-1">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Crear proveedor
                </button>
                <a href="{{ route('productos.proveedores.index') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection
