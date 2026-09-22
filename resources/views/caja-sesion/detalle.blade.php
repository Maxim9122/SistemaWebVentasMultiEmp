@extends('layouts.app')

@section('titulo', 'Detalle de caja')

@section('contenido')
    <div class="mb-4">
        <a href="{{ route('caja-sesion.show') }}" class="text-sm text-slate-500 hover:underline">&larr; Volver a Mi caja</a>
    </div>

    @include('partials.detalle-caja', ['caja' => $caja])
@endsection
