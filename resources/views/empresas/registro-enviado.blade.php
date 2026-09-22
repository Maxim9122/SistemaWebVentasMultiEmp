@extends('layouts.guest')

@section('titulo', 'Solicitud enviada')

@section('contenido')
    <div class="text-center space-y-4">
        <p class="text-lg font-semibold">¡Solicitud recibida!</p>
        <p class="text-sm text-slate-600">
            Tu solicitud de alta quedó pendiente de revisión. Te vamos a avisar por email cuando esté aprobada
            y ya puedas iniciar sesión.
        </p>
        <a href="{{ route('login') }}" class="inline-block mt-2 text-sm font-medium text-slate-900 hover:underline">
            Volver al login
        </a>
    </div>
@endsection
