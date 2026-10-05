@extends('layouts.app')

@section('titulo', 'Confirmar contraseña')

@section('contenido')
    <div class="max-w-sm mx-auto mt-8 bg-white rounded-lg shadow p-6">
        <h1 class="text-lg font-semibold mb-2">Confirmá tu contraseña</h1>
        <p class="text-sm text-slate-500 mb-4">
            Conectar Mercado Pago redirige todos los cobros con QR a la cuenta que autorices — por seguridad, confirmá tu contraseña antes de continuar.
        </p>

        <form method="POST" action="{{ route('configuracion.mercadopago.conectar.confirmar') }}">
            @csrf
            <input type="hidden" name="sandbox" value="{{ $sandbox ? '1' : '0' }}">

            <label for="password" class="block text-sm font-medium mb-1">Contraseña</label>
            <input type="password" id="password" name="password" autofocus required
                class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            @error('password')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror

            <div class="flex items-center gap-3 mt-4">
                <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Continuar a Mercado Pago
                </button>
                <a href="{{ route('configuracion.edit') }}" class="text-sm text-slate-600 hover:underline">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection
