@extends('layouts.app')

@section('titulo', 'Nuevo usuario')

@section('contenido')
    <div class="max-w-lg bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('staff.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium mb-1">Nombre</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium mb-1">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
            </div>

            <div>
                <label for="role" class="block text-sm font-medium mb-1">Rol</label>
                <select id="role" name="role" required class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Elegí un rol</option>
                    <option value="vendedor" @selected(old('role') === 'vendedor')>Vendedor</option>
                    <option value="cajero" @selected(old('role') === 'cajero')>Cajero</option>
                    <option value="cajero_vendedor" @selected(old('role') === 'cajero_vendedor')>Cajero vendedor (hace todo solo)</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-medium mb-1">Contraseña</label>
                    <input id="password" name="password" type="password" required
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium mb-1">Confirmar</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="puede_cambiar_precio_venta" value="1" @checked(old('puede_cambiar_precio_venta')) class="rounded border border-slate-300">
                Puede cambiar el precio en la venta (solo aplica si además está habilitado en Configuración)
            </label>

            <div class="flex gap-2">
                <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Crear usuario
                </button>
                <a href="{{ route('staff.index') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection
