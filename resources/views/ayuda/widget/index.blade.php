@extends('layouts.ayuda-widget')

@section('titulo', 'Centro de ayuda')

@section('contenido')
    <p class="text-sm font-semibold text-slate-900 mb-3">Centro de ayuda</p>

    <div class="space-y-1.5">
        @foreach ($secciones as $slug => $seccion)
            <a href="{{ route('ayuda.widget.seccion', $slug) }}" class="block rounded border border-slate-200 px-3 py-2 hover:bg-slate-50">
                <p class="text-sm font-medium text-slate-800">{{ $seccion['titulo'] }}</p>
                <p class="text-xs text-slate-500">{{ $seccion['descripcion'] }}</p>
            </a>
        @endforeach
    </div>
@endsection
