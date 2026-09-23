@extends('layouts.app')

@section('titulo', $tema['titulo'])

@section('contenido')
    <div class="text-sm text-slate-500 mb-4 space-x-1">
        <a href="{{ route('ayuda.index') }}" class="hover:underline">Centro de ayuda</a>
        <span>/</span>
        <a href="{{ route('ayuda.seccion', $seccionSlug) }}" class="hover:underline">{{ $seccion['titulo'] }}</a>
    </div>

    <div class="max-w-2xl bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">{{ $tema['titulo'] }}</h2>

        <div class="contenido-ayuda text-sm text-slate-700">
            {!! $tema['resumen'] !!}
        </div>
    </div>

    <style>
        .contenido-ayuda p { margin-bottom: 0.75rem; }
        .contenido-ayuda ul, .contenido-ayuda ol { margin: 0.5rem 0 0.75rem 1.25rem; }
        .contenido-ayuda ul { list-style-type: disc; }
        .contenido-ayuda ol { list-style-type: decimal; }
        .contenido-ayuda li { margin-bottom: 0.35rem; }
        .contenido-ayuda strong { font-weight: 600; color: #0f172a; }
    </style>
@endsection
