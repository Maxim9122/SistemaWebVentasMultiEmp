@extends('layouts.app')

@section('titulo', 'Empresas')

@section('contenido')
    <div class="mb-4 flex gap-2 text-sm">
        @foreach (['pendiente' => 'Pendientes', 'activa' => 'Activas', 'rechazada' => 'Rechazadas', 'suspendida' => 'Suspendidas', 'todas' => 'Todas'] as $valor => $etiqueta)
            <a href="{{ route('superadmin.empresas.index', ['estado' => $valor]) }}"
                class="rounded px-3 py-1.5 {{ $estado === $valor ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:border-slate-400' }}">
                {{ $etiqueta }}
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-lg shadow divide-y">
        @forelse ($empresas as $empresa)
            <div class="p-4 flex items-center justify-between">
                <div>
                    <a href="{{ route('superadmin.empresas.show', $empresa) }}" class="font-medium hover:underline">
                        {{ $empresa->razon_social }}
                    </a>
                    <p class="text-sm text-slate-500">{{ $empresa->cuit ? 'CUIT '.$empresa->cuit : 'Sin CUIT' }} · {{ $empresa->rubro ?? 'Sin rubro' }}</p>
                    @if ($cuitsDuplicados->contains($empresa->cuit))
                        <p class="text-xs font-medium text-amber-700 mt-1">⚠ Este CUIT se repite con otra empresa — revisar antes de aprobar</p>
                    @endif
                </div>
                <span class="text-xs font-medium rounded px-2 py-1
                    @class([
                        'bg-amber-100 text-amber-800' => $empresa->estado === 'pendiente',
                        'bg-emerald-100 text-emerald-800' => $empresa->estado === 'activa',
                        'bg-red-100 text-red-800' => $empresa->estado === 'rechazada',
                        'bg-slate-200 text-slate-700' => $empresa->estado === 'suspendida',
                    ])">
                    {{ ucfirst($empresa->estado) }}
                </span>
            </div>
        @empty
            <p class="p-4 text-sm text-slate-500">No hay empresas en este estado.</p>
        @endforelse
    </div>
@endsection
