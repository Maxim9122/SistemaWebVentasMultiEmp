@extends('layouts.app')

@section('titulo', 'Centro de ayuda')

@section('contenido')
    <p class="text-sm text-slate-500 mb-4">Elegí una sección para ver cómo se usa cada parte del sistema.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($secciones as $slug => $seccion)
            <a href="{{ route('ayuda.seccion', $slug) }}" class="block bg-white rounded-lg shadow p-5 hover:shadow-md transition-shadow">
                <p class="font-medium text-slate-900 mb-1">{{ $seccion['titulo'] }}</p>
                <p class="text-sm text-slate-500">{{ $seccion['descripcion'] }}</p>
                <p class="text-xs text-slate-400 mt-3">{{ count($seccion['temas']) }} {{ count($seccion['temas']) === 1 ? 'tema' : 'temas' }}</p>
            </a>
        @endforeach
    </div>
@endsection
