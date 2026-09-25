@extends('layouts.app')

@section('titulo', 'Editar cliente')

@section('contenido')
    <div class="max-w-lg bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('clientes.update', $cliente) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="nombre" class="block text-sm font-medium mb-1">Nombre</label>
                <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $cliente->nombre) }}" required
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
            </div>

            <div>
                <label for="cuit" class="block text-sm font-medium mb-1">CUIT / DNI</label>
                <input id="cuit" name="cuit" type="text" value="{{ old('cuit', $cliente->cuit) }}" required
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="telefono" class="block text-sm font-medium mb-1">Teléfono</label>
                    <input id="telefono" name="telefono" type="text" value="{{ old('telefono', $cliente->telefono) }}" placeholder="549..."
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium mb-1">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $cliente->email) }}"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
            </div>

            <div>
                <label for="direccion" class="block text-sm font-medium mb-1">Dirección</label>
                <input id="direccion" name="direccion" type="text" value="{{ old('direccion', $cliente->direccion) }}"
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
            </div>

            <div>
                <label for="garante" class="block text-sm font-medium mb-1">Garante</label>
                <input id="garante" name="garante" type="text" value="{{ old('garante', $cliente->garante) }}"
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Guardar cambios
                </button>
                <a href="{{ route('clientes.index') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection
