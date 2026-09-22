@extends('layouts.app')

@section('titulo', 'Clientes')

@section('contenido')
    <div class="mb-4 flex flex-wrap justify-end gap-2">
        <a href="{{ route('clientes.importaciones.index') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            Importaciones
        </a>
        <a href="{{ route('clientes.importar.subir') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            Importar desde Excel
        </a>
        <button type="button" id="btn_exportar_clientes" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            Descargar Excel
        </button>
        <a href="{{ route('clientes.create') }}" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
            + Nuevo cliente
        </a>
    </div>

    @include('partials.modal-confirmacion', [
        'id' => 'modal_exportar_clientes',
        'titulo' => 'Descargar Excel',
        'mensaje' => $buscar !== ''
            ? 'Se va a descargar un Excel con los clientes que coinciden con "'.$buscar.'".'
            : 'Se va a descargar un Excel con los '.$clientes->total().' cliente(s) de tu empresa.',
        'textoConfirmar' => 'Descargar',
    ])

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('clientes.index') }}" class="flex items-center gap-2">
            <input type="text" name="buscar" value="{{ $buscar }}" placeholder="Buscar por nombre, CUIT, teléfono o email..."
                class="w-72 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            <button type="submit" class="rounded px-3 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                Buscar
            </button>
            @if ($buscar !== '')
                <a href="{{ route('clientes.index', ['por_pagina' => $porPagina]) }}" class="text-sm text-slate-500 hover:underline">
                    Limpiar
                </a>
            @endif
        </form>

        <form method="GET" action="{{ route('clientes.index') }}" class="flex items-center gap-2 text-sm">
            <input type="hidden" name="buscar" value="{{ $buscar }}">
            <label for="por_pagina" class="text-slate-500">Mostrar</label>
            <select id="por_pagina" name="por_pagina" onchange="this.form.submit()"
                class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                <option value="10" @selected($porPagina === 10)>10</option>
                <option value="50" @selected($porPagina === 50)>50</option>
                <option value="100" @selected($porPagina === 100)>100</option>
            </select>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">Nombre</th>
                    <th class="px-4 py-2 font-medium">CUIT / DNI</th>
                    <th class="px-4 py-2 font-medium">Teléfono</th>
                    <th class="px-4 py-2 font-medium">Email</th>
                    <th class="px-4 py-2 font-medium">Estado</th>
                    <th class="px-4 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($clientes as $cliente)
                    <tr>
                        <td class="px-4 py-2 font-medium">{{ $cliente->nombre }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $cliente->cuit ?? '—' }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $cliente->telefono ?? '—' }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $cliente->email ?? '—' }}</td>
                        <td class="px-4 py-2">
                            @if ($cliente->activo)
                                <span class="text-xs font-medium rounded px-2 py-1 bg-emerald-100 text-emerald-800">Activo</span>
                            @else
                                <span class="text-xs font-medium rounded px-2 py-1 bg-slate-200 text-slate-700">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <a href="{{ route('clientes.edit', $cliente) }}" class="text-sm text-slate-600 hover:underline">Editar</a>
                            @if ($cliente->activo)
                                <form method="POST" action="{{ route('clientes.desactivar', $cliente) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="ml-3 text-sm text-red-600 hover:underline">Desactivar</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('clientes.activar', $cliente) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="ml-3 text-sm text-emerald-700 hover:underline">Activar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">No hay clientes que coincidan con la búsqueda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $clientes->links() }}
    </div>

    <script>
        (function () {
            var boton = document.getElementById('btn_exportar_clientes');
            var modal = document.getElementById('modal_exportar_clientes');

            boton.addEventListener('click', function () {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });

            document.getElementById('modal_exportar_clientes_cancelar').addEventListener('click', function () {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });

            document.getElementById('modal_exportar_clientes_confirmar').addEventListener('click', function () {
                window.location.href = "{{ route('clientes.exportar', ['buscar' => $buscar]) }}";
            });
        })();
    </script>
@endsection
