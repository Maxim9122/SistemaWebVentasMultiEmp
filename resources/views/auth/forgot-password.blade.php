@extends('layouts.guest')

@section('titulo', 'Recuperar contraseña')

@section('contenido')
    <p class="text-sm text-slate-600 mb-4">
        Ingresá el email con el que te registraste y te mandamos un link para poner una contraseña nueva.
    </p>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium mb-1">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
        </div>

        <button type="submit" class="w-full rounded bg-slate-900 text-white py-2 text-sm font-medium hover:bg-slate-800">
            Mandar link de recuperación
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        <a href="{{ route('login') }}" class="text-slate-900 font-medium hover:underline">&larr; Volver a iniciar sesión</a>
    </p>
@endsection
