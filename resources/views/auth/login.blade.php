@extends('layouts.guest')

@section('titulo', 'Acceder')

@section('contenido')
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium mb-1">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium mb-1">Contraseña</label>
            <input id="password" name="password" type="password" required
                class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="rounded border border-slate-300">
            Recordarme
        </label>

        <button type="submit" class="w-full rounded bg-slate-900 text-white py-2 text-sm font-medium hover:bg-slate-800">
            Entrar
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        ¿Tu empresa todavía no tiene cuenta?
        <a href="{{ route('empresas.registro') }}" class="text-slate-900 font-medium hover:underline">Registrala acá</a>
    </p>
@endsection
