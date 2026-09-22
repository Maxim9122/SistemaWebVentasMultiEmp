@extends('layouts.app')

@section('titulo', 'Resultado de la importación')

@section('contenido')
    <div class="bg-white rounded-lg shadow p-6 space-y-4">
        <div class="flex gap-6 text-sm">
            <div>
                <p class="text-2xl font-semibold text-emerald-700">{{ $creados }}</p>
                <p class="text-slate-500">Creados</p>
            </div>
            <div>
                <p class="text-2xl font-semibold text-sky-700">{{ $actualizados }}</p>
                <p class="text-slate-500">Actualizados</p>
            </div>
            <div>
                <p class="text-2xl font-semibold text-red-700">{{ count($errores) }}</p>
                <p class="text-slate-500">Con error</p>
            </div>
        </div>

        @if (count($errores) > 0)
            <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-medium mb-1">Filas que no se pudieron importar:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errores as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex gap-2 pt-2">
            <a href="{{ route('productos.index') }}" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                Ver productos
            </a>
            <a href="{{ route('productos.importar.subir') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                Importar otro archivo
            </a>
        </div>
    </div>
@endsection
