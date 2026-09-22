@extends('layouts.app')

@section('titulo', 'Resumen')

@section('contenido')
    <div class="bg-white rounded-lg shadow p-6 space-y-2">
        <p class="text-lg font-semibold">Hola, {{ $user->name }}</p>
        <p class="text-sm text-slate-600">Empresa: {{ $user->empresa->razon_social }}</p>
        <p class="text-sm text-slate-600">
            Rol:
            <span class="inline-block rounded bg-slate-100 px-2 py-0.5 font-medium">
                {{ str_replace('_', ' ', $user->role) }}
            </span>
        </p>
    </div>
@endsection
