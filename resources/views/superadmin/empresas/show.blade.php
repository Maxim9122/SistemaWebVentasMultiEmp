@extends('layouts.app')

@section('titulo', $empresa->razon_social)

@section('contenido')
    <a href="{{ route('superadmin.empresas.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Volver</a>

    @if ($empresasConMismoCuit->isNotEmpty())
        <div class="mt-4 rounded border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-medium mb-1">⚠ El CUIT {{ $empresa->cuit }} también lo tiene:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($empresasConMismoCuit as $otra)
                    <li>
                        <a href="{{ route('superadmin.empresas.show', $otra) }}" class="underline hover:no-underline">{{ $otra->razon_social }}</a>
                        — {{ ucfirst($otra->estado) }}, registrada el {{ $otra->created_at->format('d/m/Y') }}
                    </li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs text-amber-700">
                Antes de aprobar, valdría la pena contactar al dueño de la otra empresa y confirmar si esta también le pertenece.
            </p>
        </div>
    @endif

    <div class="mt-4 bg-white rounded-lg shadow p-6 space-y-6">
        <div>
            <h2 class="text-sm font-semibold text-slate-700 mb-2">Datos de la empresa</h2>
            <dl class="grid grid-cols-2 gap-y-2 text-sm">
                <dt class="text-slate-500">Razón social</dt>
                <dd>{{ $empresa->razon_social }}</dd>
                <dt class="text-slate-500">CUIT</dt>
                <dd>{{ $empresa->cuit ?? '—' }}</dd>
                <dt class="text-slate-500">Rubro</dt>
                <dd>{{ $empresa->rubro ?? '—' }}</dd>
                <dt class="text-slate-500">Email de contacto</dt>
                <dd>{{ $empresa->email_contacto }}</dd>
                <dt class="text-slate-500">Teléfono</dt>
                <dd>{{ $empresa->telefono ?? '—' }}</dd>
                <dt class="text-slate-500">Dirección</dt>
                <dd>{{ $empresa->direccion ?? '—' }}</dd>
                <dt class="text-slate-500">Estado</dt>
                <dd class="font-medium">{{ ucfirst($empresa->estado) }}</dd>
                @if ($empresa->motivo_rechazo)
                    <dt class="text-slate-500">Motivo de rechazo</dt>
                    <dd>{{ $empresa->motivo_rechazo }}</dd>
                @endif
                @if ($empresa->motivo_suspension)
                    <dt class="text-slate-500">Motivo de suspensión</dt>
                    <dd>{{ $empresa->motivo_suspension }}</dd>
                @endif
            </dl>
        </div>

        <div>
            <h2 class="text-sm font-semibold text-slate-700 mb-2">Usuario administrador</h2>
            @foreach ($empresa->users as $usuario)
                <p class="text-sm">{{ $usuario->name }} — {{ $usuario->email }}</p>
            @endforeach
        </div>

        <div class="flex gap-2 pt-2 border-t">
            @if ($empresa->estado === 'pendiente')
                <form method="POST" action="{{ route('superadmin.empresas.aprobar', $empresa) }}">
                    @csrf
                    <button type="submit" class="rounded bg-emerald-600 text-white px-4 py-2 text-sm font-medium hover:bg-emerald-700">Aprobar</button>
                </form>
                <form method="POST" action="{{ route('superadmin.empresas.rechazar', $empresa) }}" class="flex items-center gap-2">
                    @csrf
                    <input type="text" name="motivo" placeholder="Motivo (opcional)" class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <button type="submit" class="rounded bg-red-600 text-white px-4 py-2 text-sm font-medium hover:bg-red-700">Rechazar</button>
                </form>
            @elseif ($empresa->estado === 'activa')
                <form method="POST" action="{{ route('superadmin.empresas.suspender', $empresa) }}" class="flex items-center gap-2">
                    @csrf
                    <input type="text" name="motivo" placeholder="Motivo (opcional)" class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <button type="submit" class="rounded bg-amber-600 text-white px-4 py-2 text-sm font-medium hover:bg-amber-700">Suspender</button>
                </form>
            @elseif (in_array($empresa->estado, ['rechazada', 'suspendida']))
                <form method="POST" action="{{ route('superadmin.empresas.reactivar', $empresa) }}">
                    @csrf
                    <button type="submit" class="rounded bg-emerald-600 text-white px-4 py-2 text-sm font-medium hover:bg-emerald-700">Reactivar</button>
                </form>
            @endif
        </div>
    </div>
@endsection
