@extends('layouts.guest')

@section('titulo', 'Nueva contraseña')

@section('contenido')
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="block text-sm font-medium mb-1">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autofocus
                class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium mb-1">Contraseña nueva</label>
            <input id="password" name="password" type="password" required
                class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium mb-1">Confirmar contraseña</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
        </div>

        <button type="submit" class="w-full rounded bg-slate-900 text-white py-2 text-sm font-medium hover:bg-slate-800">
            Guardar contraseña
        </button>
    </form>
@endsection
