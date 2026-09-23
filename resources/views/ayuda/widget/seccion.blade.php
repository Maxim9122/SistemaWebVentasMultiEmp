@extends('layouts.ayuda-widget')

@section('titulo', $seccion['titulo'])

@section('contenido')
    <a href="{{ route('ayuda.widget.index') }}" class="text-xs text-slate-500 hover:underline">&larr; Centro de ayuda</a>

    <p class="text-sm font-semibold text-slate-900 mt-2 mb-3">{{ $seccion['titulo'] }}</p>

    <div class="space-y-1.5">
        @foreach ($seccion['temas'] as $temaSlug => $tema)
            <a href="{{ route('ayuda.widget.tema', [$slug, $temaSlug]) }}" class="block rounded border border-slate-200 px-3 py-2 text-sm text-slate-800 hover:bg-slate-50">
                {{ $tema['titulo'] }}
            </a>
        @endforeach
    </div>
@endsection
