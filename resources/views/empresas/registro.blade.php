@extends('layouts.guest')

@section('titulo', 'Registrar empresa')
@section('ancho', 'max-w-xl')

@section('contenido')
    <form method="POST" action="{{ route('empresas.registro.store') }}" class="space-y-6">
        @csrf

        <div>
            <h2 class="text-sm font-semibold text-slate-700 mb-3">Datos de la empresa</h2>
            <div class="space-y-4">
                <div>
                    <label for="razon_social" class="block text-sm font-medium mb-1">Razón social</label>
                    <input id="razon_social" name="razon_social" type="text" value="{{ old('razon_social') }}" required
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="cuit" class="block text-sm font-medium mb-1">CUIT</label>
                        <input id="cuit" name="cuit" type="text" value="{{ old('cuit') }}" required
                            class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <div>
                        <label for="rubro" class="block text-sm font-medium mb-1">Rubro</label>
                        <input id="rubro" name="rubro" type="text" value="{{ old('rubro') }}" placeholder="Corralón, multirrubro, indumentaria..."
                            class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                </div>

                <div>
                    <label for="email_contacto" class="block text-sm font-medium mb-1">Email de contacto</label>
                    <input id="email_contacto" name="email_contacto" type="email" value="{{ old('email_contacto') }}" required
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="telefono" class="block text-sm font-medium mb-1">Teléfono</label>
                        <input id="telefono" name="telefono" type="text" value="{{ old('telefono') }}"
                            class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <div>
                        <label for="direccion" class="block text-sm font-medium mb-1">Dirección</label>
                        <input id="direccion" name="direccion" type="text" value="{{ old('direccion') }}"
                            class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-sm font-semibold text-slate-700 mb-3">Tu usuario administrador</h2>
            <div class="space-y-4">
                <div>
                    <label for="admin_name" class="block text-sm font-medium mb-1">Nombre</label>
                    <input id="admin_name" name="admin_name" type="text" value="{{ old('admin_name') }}" required
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>

                <div>
                    <label for="admin_email" class="block text-sm font-medium mb-1">Email</label>
                    <input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" required
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="admin_password" class="block text-sm font-medium mb-1">Contraseña</label>
                        <input id="admin_password" name="admin_password" type="password" required
                            class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <div>
                        <label for="admin_password_confirmation" class="block text-sm font-medium mb-1">Confirmar contraseña</label>
                        <input id="admin_password_confirmation" name="admin_password_confirmation" type="password" required
                            class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                </div>
            </div>
        </div>

        @if (config('services.recaptcha.site_key'))
            <div>
                <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                @error('g-recaptcha-response')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <button type="submit" class="w-full rounded bg-slate-900 text-white py-2 text-sm font-medium hover:bg-slate-800">
            Enviar solicitud de alta
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        ¿Ya tenés cuenta?
        <a href="{{ route('login') }}" class="text-slate-900 font-medium hover:underline">Iniciá sesión</a>
    </p>

    @if (config('services.recaptcha.site_key'))
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
@endsection
