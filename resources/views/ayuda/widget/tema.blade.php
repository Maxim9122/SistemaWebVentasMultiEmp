@extends('layouts.ayuda-widget')

@section('titulo', $tema['titulo'])

@section('contenido')
    <div class="text-xs text-slate-500 mb-2 space-x-1">
        <a href="{{ route('ayuda.widget.index') }}" class="hover:underline">Ayuda</a>
        <span>/</span>
        <a href="{{ route('ayuda.widget.seccion', $seccionSlug) }}" class="hover:underline">{{ $seccion['titulo'] }}</a>
    </div>

    <p class="text-sm font-semibold text-slate-900 mb-2">{{ $tema['titulo'] }}</p>

    <div class="contenido-ayuda text-sm text-slate-700">
        {!! $tema['resumen'] !!}
    </div>

    <style>
        .contenido-ayuda p { margin-bottom: 0.6rem; }
        .contenido-ayuda ul, .contenido-ayuda ol { margin: 0.4rem 0 0.6rem 1.1rem; }
        .contenido-ayuda ul { list-style-type: disc; }
        .contenido-ayuda ol { list-style-type: decimal; }
        .contenido-ayuda li { margin-bottom: 0.3rem; }
        .contenido-ayuda strong { font-weight: 600; color: #0f172a; }
    </style>
@endsection
