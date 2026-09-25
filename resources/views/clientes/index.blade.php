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
        <button type="button" id="btn_exportar_clientes" class="inline-flex items-center gap-1.5 rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                <path d="M12 4v10m0 0-3.5-3.5M12 14l3.5-3.5"/>
                <path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>
            </svg>
            Descargar Excel
        </button>
        <a href="{{ route('clientes.create') }}" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
            + Nuevo cliente
        </a>
    </div>

    {{-- Modal: editar cliente --}}
    <div id="modal_editar_cliente" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h2 class="text-lg font-semibold">Editar cliente</h2>
                <button type="button" id="btn_cerrar_editar_cliente" class="text-slate-400 hover:text-slate-600 p-1" aria-label="Cerrar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                        <path d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            <form method="POST" id="form_editar_cliente" class="px-6 py-4 overflow-y-auto flex-1 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="editar_cliente_nombre" class="block text-sm font-medium mb-1">Nombre</label>
                    <input id="editar_cliente_nombre" name="nombre" type="text" required
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>

                <div>
                    <label for="editar_cliente_cuit" class="block text-sm font-medium mb-1">CUIT / DNI</label>
                    <input id="editar_cliente_cuit" name="cuit" type="text" required
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="editar_cliente_telefono" class="block text-sm font-medium mb-1">Teléfono</label>
                        <input id="editar_cliente_telefono" name="telefono" type="text" placeholder="549..."
                            class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <div>
                        <label for="editar_cliente_email" class="block text-sm font-medium mb-1">Email</label>
                        <input id="editar_cliente_email" name="email" type="email"
                            class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                </div>

                <div>
                    <label for="editar_cliente_direccion" class="block text-sm font-medium mb-1">Dirección</label>
                    <input id="editar_cliente_direccion" name="direccion" type="text"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>

                <div>
                    <label for="editar_cliente_garante" class="block text-sm font-medium mb-1">Garante</label>
                    <input id="editar_cliente_garante" name="garante" type="text"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
            </form>

            <div class="px-6 py-4 border-t flex justify-end gap-2">
                <button type="button" id="btn_cancelar_editar_cliente" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </button>
                <button type="submit" form="form_editar_cliente" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Guardar cambios
                </button>
            </div>
        </div>
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
            <button type="submit" class="inline-flex items-center gap-1.5 rounded px-3 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m20 20-3.5-3.5"/>
                </svg>
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
                            <button type="button" class="btn-editar-cliente text-sm text-slate-600 hover:underline"
                                data-url="{{ route('clientes.update', $cliente) }}"
                                data-nombre="{{ $cliente->nombre }}"
                                data-cuit="{{ $cliente->cuit }}"
                                data-telefono="{{ $cliente->telefono }}"
                                data-email="{{ $cliente->email }}"
                                data-direccion="{{ $cliente->direccion }}"
                                data-garante="{{ $cliente->garante }}">
                                Editar
                            </button>
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
            var modal = document.getElementById('modal_editar_cliente');
            var form = document.getElementById('form_editar_cliente');
            var campoNombre = document.getElementById('editar_cliente_nombre');
            var campoCuit = document.getElementById('editar_cliente_cuit');
            var campoTelefono = document.getElementById('editar_cliente_telefono');
            var campoEmail = document.getElementById('editar_cliente_email');
            var campoDireccion = document.getElementById('editar_cliente_direccion');
            var campoGarante = document.getElementById('editar_cliente_garante');

            function cerrarModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            document.querySelectorAll('.btn-editar-cliente').forEach(function (boton) {
                boton.addEventListener('click', function () {
                    form.action = boton.dataset.url;
                    campoNombre.value = boton.dataset.nombre || '';
                    campoCuit.value = boton.dataset.cuit || '';
                    campoTelefono.value = boton.dataset.telefono || '';
                    campoEmail.value = boton.dataset.email || '';
                    campoDireccion.value = boton.dataset.direccion || '';
                    campoGarante.value = boton.dataset.garante || '';

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    campoNombre.focus();
                });
            });

            document.getElementById('btn_cerrar_editar_cliente').addEventListener('click', cerrarModal);
            document.getElementById('btn_cancelar_editar_cliente').addEventListener('click', cerrarModal);
        })();

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
