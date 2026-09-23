@extends('layouts.app')

@section('titulo', $seccion['titulo'])

@section('contenido')
    <a href="{{ route('ayuda.index') }}" class="text-sm text-slate-500 hover:underline mb-4 inline-block">&larr; Centro de ayuda</a>

    <p class="text-sm text-slate-500 mb-4">{{ $seccion['descripcion'] }}</p>

    <div class="bg-white rounded-lg shadow divide-y">
        @foreach ($seccion['temas'] as $temaSlug => $tema)
            <a href="{{ route('ayuda.tema', [$slug, $temaSlug]) }}" class="block p-4 hover:bg-slate-50">
                <p class="font-medium text-slate-900">{{ $tema['titulo'] }}</p>
            </a>
        @endforeach
    </div>
@endsection
