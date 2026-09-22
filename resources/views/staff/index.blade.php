@extends('layouts.app')

@section('titulo', 'Staff')

@section('contenido')
    <div class="mb-4 flex justify-end gap-3">
        <a href="{{ route('staff.seguridad.edit') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            Seguridad de acceso
        </a>
        <a href="{{ route('staff.sesiones') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            Historial de sesiones
        </a>
        <a href="{{ route('staff.create') }}" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
            + Nuevo usuario
        </a>
    </div>

    <div class="bg-white rounded-lg shadow divide-y">
        @forelse ($staff as $usuario)
            <div class="p-4 flex items-center justify-between">
                <div>
                    <a href="{{ route('staff.edit', $usuario) }}" class="font-medium hover:underline">{{ $usuario->name }}</a>
                    <p class="text-sm text-slate-500">{{ $usuario->email }} · {{ str_replace('_', ' ', $usuario->role) }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-medium rounded px-2 py-1 @class(['bg-emerald-100 text-emerald-800' => $usuario->activo, 'bg-slate-200 text-slate-700' => ! $usuario->activo])">
                        {{ $usuario->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                    <a href="{{ route('staff.edit', $usuario) }}" class="rounded px-3 py-1.5 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                        Editar
                    </a>
                    @if ($usuario->activo)
                        <form method="POST" action="{{ route('staff.desactivar', $usuario) }}">
                            @csrf
                            <button type="submit" class="text-sm text-red-600 hover:underline">Desactivar</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('staff.activar', $usuario) }}">
                            @csrf
                            <button type="submit" class="text-sm text-emerald-600 hover:underline">Activar</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="p-4 text-sm text-slate-500">Todavía no cargaste ningún usuario. Empezá con "+ Nuevo usuario".</p>
        @endforelse
    </div>
@endsection
