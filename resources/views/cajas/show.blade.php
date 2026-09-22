@extends('layouts.app')

@section('titulo', 'Detalle de caja')

@section('contenido')
    <div class="mb-4">
        <a href="{{ route('cajas.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Volver a Historial de cajas</a>
    </div>

    @include('partials.detalle-caja', ['caja' => $caja])
@endsection
