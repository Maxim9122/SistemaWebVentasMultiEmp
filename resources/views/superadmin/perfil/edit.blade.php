@extends('layouts.app')

@section('titulo', 'Mi perfil')

@section('contenido')
    <div class="max-w-lg bg-white rounded-lg shadow p-6 mb-4">
        <p class="font-medium text-slate-900 mb-1">Ícono del sitio</p>
        <p class="text-sm text-slate-500 mb-3">Se usa como ícono de la pestaña del navegador en todo el sitio (login y panel). Ideal: imagen cuadrada.</p>

        @if ($errors->has('icono'))
            <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first('icono') }}
            </div>
        @endif

        <div class="flex items-center gap-4">
            @if ($usuario->iconoSitioUrl())
                <img src="{{ $usuario->iconoSitioUrl() }}" alt="Ícono del sitio" class="w-16 h-16 object-cover rounded-2xl border border-slate-200">
            @else
                <div class="w-16 h-16 rounded-2xl border border-dashed border-slate-300 flex items-center justify-center text-xs text-slate-400 text-center px-1">
                    Por defecto
                </div>
            @endif

            <form method="POST" action="{{ route('superadmin.perfil.icono.update') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                @csrf
                @method('PUT')
                <input type="file" name="icono" accept="image/png,image/jpeg,image/webp" required
                    class="text-sm text-slate-600 file:mr-3 file:rounded file:border-0 file:bg-slate-900 file:text-white file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-slate-800">
                <button type="submit" class="rounded bg-slate-900 text-white px-3 py-1.5 text-sm font-medium hover:bg-slate-800">
                    {{ $usuario->iconoSitioUrl() ? 'Cambiar' : 'Subir' }}
                </button>
            </form>

            @if ($usuario->iconoSitioUrl())
                <form method="POST" action="{{ route('superadmin.perfil.icono.destroy') }}" onsubmit="return confirm('¿Volver al ícono por defecto?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-slate-500 hover:text-red-600 hover:underline">Quitar</button>
                </form>
            @endif
        </div>
    </div>

    <div class="max-w-lg bg-white rounded-lg shadow p-6">
        <p class="text-sm text-slate-500 mb-4">
            El email de acá abajo es el que usás para iniciar sesión — nunca se muestra en ningún lado del sistema.
            El teléfono y el "email público" son los datos de contacto de LunaSoft que se muestran en el pie de
            todas las pantallas.
        </p>

        <form method="POST" action="{{ route('superadmin.perfil.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium mb-1">Nombre</label>
                <input id="name" name="name" type="text" value="{{ old('name', $usuario->name) }}" required
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium mb-1">Email para iniciar sesión</label>
                <input id="email" name="email" type="email" value="{{ old('email', $usuario->email) }}" required
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                <p class="text-xs text-slate-400 mt-1">Privado — nunca se muestra en el footer ni en ningún otro lado.</p>
            </div>

            <div>
                <label for="email_publico" class="block text-sm font-medium mb-1">Email público (footer)</label>
                <input id="email_publico" name="email_publico" type="email" value="{{ old('email_publico', $usuario->email_publico) }}"
                    placeholder="ej: contacto@lunasoft.com"
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                <p class="text-xs text-slate-400 mt-1">Este es el que ve todo el mundo en el pie de página. Dejalo vacío para no mostrar ningún email ahí.</p>
            </div>

            <div>
                <label for="telefono" class="block text-sm font-medium mb-1">Teléfono</label>
                <input id="telefono" name="telefono" type="text" value="{{ old('telefono', $usuario->telefono) }}"
                    placeholder="ej: +54 9 11 1234-5678"
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-medium mb-1">Nueva contraseña</label>
                    <input id="password" name="password" type="password" placeholder="Dejar en blanco para no cambiarla"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium mb-1">Confirmar</label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
            </div>

            <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                Guardar cambios
            </button>
        </form>
    </div>
@endsection
