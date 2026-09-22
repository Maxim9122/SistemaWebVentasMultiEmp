@extends('layouts.app')

@section('titulo', 'Créditos')

@section('contenido')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('creditos.index') }}" id="form_filtro_creditos" class="flex flex-wrap items-end gap-2">
            <div class="relative">
                <label for="cliente_buscador_filtro" class="block text-xs text-slate-500 mb-1">Cliente</label>
                <input type="text" id="cliente_buscador_filtro" autocomplete="off" placeholder="Buscar cliente..."
                    value="{{ $cliente ? $cliente->nombre.($cliente->cuit ? ' ('.$cliente->cuit.')' : '') : '' }}"
                    class="w-56 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                <ul id="resultados_cliente_filtro" class="hidden absolute z-10 mt-1 w-64 max-h-48 overflow-auto rounded border border-slate-200 bg-white shadow-lg"></ul>
                <input type="hidden" name="cliente_id" id="cliente_id_filtro" value="{{ $cliente?->id }}">
            </div>
            <div>
                <label for="fecha_desde" class="block text-xs text-slate-500 mb-1">Desde</label>
                <input type="date" id="fecha_desde" name="fecha_desde" value="{{ $fechaDesde }}"
                    class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            </div>
            <div>
                <label for="fecha_hasta" class="block text-xs text-slate-500 mb-1">Hasta</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta" value="{{ $fechaHasta }}"
                    class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            </div>
            <button type="submit" class="rounded px-3 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                Buscar
            </button>
            @if ($cliente || $fechaFiltradaManualmente)
                <a href="{{ route('creditos.index', ['por_pagina' => $porPagina]) }}" class="text-sm text-slate-500 hover:underline pb-2">
                    Limpiar
                </a>
            @endif
        </form>

        <div class="flex items-center gap-3">
            <button type="button" id="btn_abrir_pago_credito" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                Registrar pago
            </button>
            <form method="GET" action="{{ route('creditos.index') }}" class="flex items-center gap-2 text-sm">
                <input type="hidden" name="cliente_id" value="{{ $cliente?->id }}">
                <input type="hidden" name="fecha_desde" value="{{ $fechaDesde }}">
                <input type="hidden" name="fecha_hasta" value="{{ $fechaHasta }}">
                <label for="por_pagina" class="text-slate-500">Mostrar</label>
                <select id="por_pagina" name="por_pagina" onchange="this.form.submit()"
                    class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="10" @selected($porPagina === 10)>10</option>
                    <option value="50" @selected($porPagina === 50)>50</option>
                    <option value="100" @selected($porPagina === 100)>100</option>
                </select>
            </form>
        </div>
    </div>

    @if ($cliente)
        <div class="bg-white rounded-lg shadow p-6 mb-4">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <p class="text-sm text-slate-500 mb-1">Cliente</p>
                    <p class="text-lg font-semibold">{{ $cliente->nombre }} @if ($cliente->cuit) <span class="text-sm font-normal text-slate-500">({{ $cliente->cuit }})</span> @endif</p>
                </div>
                <button type="button" id="btn_ver_pagos_cliente" class="rounded px-3 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400 whitespace-nowrap">
                    Ver detalle de pagos ({{ $pagosCliente->count() }})
                </button>
            </div>

            <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-slate-500">Total fiado (histórico)</p>
                    <p class="font-medium">${{ number_format($cliente->totalFiado(), 2, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Total pagado</p>
                    <p class="font-medium text-emerald-600">${{ number_format($cliente->totalPagadoCredito(), 2, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Saldo pendiente</p>
                    <p class="font-medium text-lg @class(['text-orange-600' => $cliente->saldoPendiente() > 0])">
                        ${{ number_format($cliente->saldoPendiente(), 2, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Modal: detalle de pagos parciales del cliente --}}
        <div id="modal_pagos_cliente" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[80vh] overflow-y-auto p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold">Historial de pagos de {{ $cliente->nombre }}</h2>
                    <button type="button" id="btn_cerrar_pagos_cliente" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-left">
                        <tr>
                            <th class="px-4 py-2 font-medium">Fecha y hora</th>
                            <th class="px-4 py-2 font-medium">Registrado por</th>
                            <th class="px-4 py-2 font-medium text-right">Efectivo</th>
                            <th class="px-4 py-2 font-medium text-right">Tarjeta</th>
                            <th class="px-4 py-2 font-medium text-right">Transferencia</th>
                            <th class="px-4 py-2 font-medium text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($pagosCliente as $pago)
                            <tr>
                                <td class="px-4 py-2 text-slate-500">{{ $pago->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2">{{ $pago->usuario->name }}</td>
                                <td class="px-4 py-2 text-right">{{ $pago->monto_efectivo > 0 ? '$'.number_format($pago->monto_efectivo, 2, ',', '.') : '—' }}</td>
                                <td class="px-4 py-2 text-right">{{ $pago->monto_tarjeta > 0 ? '$'.number_format($pago->monto_tarjeta, 2, ',', '.') : '—' }}</td>
                                <td class="px-4 py-2 text-right">{{ $pago->monto_transferencia > 0 ? '$'.number_format($pago->monto_transferencia, 2, ',', '.') : '—' }}</td>
                                <td class="px-4 py-2 text-right font-medium">${{ number_format($pago->total(), 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-slate-500">Este cliente todavía no hizo ningún pago parcial.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <p class="px-4 py-2 text-sm font-medium border-b bg-slate-50">
            Ventas fiadas {{ $cliente ? 'de '.$cliente->nombre : '' }}
        </p>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium"></th>
                    <th class="px-4 py-2 font-medium">N°</th>
                    <th class="px-4 py-2 font-medium">Fecha</th>
                    <th class="px-4 py-2 font-medium">Cliente</th>
                    <th class="px-4 py-2 font-medium text-right">Total venta</th>
                    <th class="px-4 py-2 font-medium text-right">Quedó fiado</th>
                    <th class="px-4 py-2 font-medium">Promesa de pago</th>
                    <th class="px-4 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($ventasFiadas as $venta)
                    <tr @class(['bg-orange-50' => $venta->cliente?->promesaPagoProxima()])>
                        <td class="px-4 py-2">
                            @if ($venta->cliente)
                                <input type="checkbox" class="seleccion-fiada rounded border border-slate-300" data-id="{{ $venta->cliente_id }}">
                            @endif
                        </td>
                        <td class="px-4 py-2 font-medium">{{ $venta->numero_venta }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $venta->cobrado_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2">{{ $venta->cliente->nombre ?? '—' }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($venta->total, 2, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right font-medium text-red-600">${{ number_format($venta->monto_fiado, 2, ',', '.') }}</td>
                        <td class="px-4 py-2">
                            @if ($venta->cliente)
                                <form method="POST" action="{{ route('creditos.clientes.promesaPago.update', $venta->cliente) }}" class="flex items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="date" name="fecha_promesa_pago" value="{{ $venta->cliente->fecha_promesa_pago?->format('Y-m-d') }}"
                                        class="rounded border text-sm focus:border-slate-500 focus:ring-slate-500 @class(['border-orange-400 text-orange-800 font-medium' => $venta->cliente->promesaPagoProxima(), 'border-slate-300' => ! $venta->cliente->promesaPagoProxima()])">
                                    <button type="submit" class="text-xs text-slate-500 hover:underline whitespace-nowrap">Guardar</button>
                                </form>
                                @if ($venta->cliente->fecha_promesa_pago)
                                    <form method="POST" action="{{ route('creditos.clientes.promesaPago.update', $venta->cliente) }}" class="mt-1">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="fecha_promesa_pago" value="">
                                        <button type="submit" class="text-xs text-red-600 hover:underline">Quitar fecha</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <a href="{{ route('ventas.show', $venta) }}" class="text-sm text-slate-600 hover:underline">Ver venta</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-slate-500">No hay ventas fiadas que coincidan con la búsqueda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div id="bloque_seleccion_fiadas" class="hidden p-4 border-t bg-slate-50 flex flex-wrap items-end gap-3">
            <p class="text-sm w-full">
                <strong id="contador_seleccion_fiadas">0</strong> cliente(s) seleccionados
                <a href="#" id="vaciar_seleccion_fiadas" class="ml-2 text-slate-500 hover:underline">Vaciar selección</a>
            </p>
            <form method="POST" action="{{ route('creditos.promesaPagoMasiva') }}" id="form_promesa_masiva" class="flex items-end gap-2">
                @csrf
                <div>
                    <label for="fecha_promesa_masiva" class="block text-xs text-slate-500 mb-1">Fecha de promesa de pago</label>
                    <input type="date" id="fecha_promesa_masiva" name="fecha_promesa_pago" required
                        class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div id="ids_seleccionados_fiadas"></div>
                <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Asignar a los seleccionados
                </button>
            </form>
        </div>
    </div>

    <div class="mt-4">
        {{ $ventasFiadas->links() }}
    </div>

    {{-- Modal: registrar pago parcial --}}
    <div id="modal_pago_credito" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Registrar pago</h2>
                <button type="button" id="btn_cerrar_pago_credito" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <form method="POST" action="{{ route('creditos.pagos.store') }}" class="space-y-3">
                @csrf

                <div class="relative">
                    <label for="cliente_buscador_modal" class="block text-sm font-medium mb-1">Cliente</label>
                    <input type="text" id="cliente_buscador_modal" autocomplete="off" placeholder="Buscar cliente..."
                        value="{{ $cliente ? $cliente->nombre.($cliente->cuit ? ' ('.$cliente->cuit.')' : '') : '' }}"
                        class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <ul id="resultados_cliente_modal" class="hidden absolute z-10 mt-1 w-full max-h-48 overflow-auto rounded border border-slate-200 bg-white shadow-lg"></ul>
                    <input type="hidden" name="cliente_id" id="cliente_id_modal" value="{{ $cliente?->id }}">
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label for="pago_efectivo" class="block text-sm font-medium mb-1">Efectivo</label>
                        <input type="number" id="pago_efectivo" name="monto_efectivo" step="0.01" min="0"
                            class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <div>
                        <label for="pago_tarjeta" class="block text-sm font-medium mb-1">Tarjeta</label>
                        <input type="number" id="pago_tarjeta" name="monto_tarjeta" step="0.01" min="0"
                            class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <div>
                        <label for="pago_transferencia" class="block text-sm font-medium mb-1">Transferencia</label>
                        <input type="number" id="pago_transferencia" name="monto_transferencia" step="0.01" min="0"
                            class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                        Guardar pago
                    </button>
                    <button type="button" id="btn_cancelar_pago_credito" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const clientes = @json($clientesParaBuscador);

            function conectarBuscadorCliente(inputId, listaId, hiddenId) {
                const input = document.getElementById(inputId);
                const lista = document.getElementById(listaId);
                const hidden = document.getElementById(hiddenId);
                let resultados = [];

                function renderizar() {
                    lista.innerHTML = '';

                    if (resultados.length === 0) {
                        lista.classList.add('hidden');
                        return;
                    }

                    resultados.forEach(function (c) {
                        const li = document.createElement('li');
                        li.textContent = c.nombre + (c.cuit ? ' (' + c.cuit + ')' : '');
                        li.className = 'px-3 py-2 text-sm cursor-pointer hover:bg-slate-100';
                        li.addEventListener('mousedown', function (evento) {
                            evento.preventDefault();
                            input.value = c.nombre + (c.cuit ? ' (' + c.cuit + ')' : '');
                            hidden.value = c.id;
                            resultados = [];
                            renderizar();
                        });
                        lista.appendChild(li);
                    });

                    lista.classList.remove('hidden');
                }

                input.addEventListener('input', function () {
                    hidden.value = '';
                    const texto = input.value.trim().toLowerCase();
                    resultados = texto === '' ? [] : clientes.filter(function (c) {
                        return c.nombre.toLowerCase().includes(texto) || (c.cuit && c.cuit.toLowerCase().includes(texto));
                    }).slice(0, 8);
                    renderizar();
                });

                input.addEventListener('blur', function () {
                    setTimeout(function () { resultados = []; renderizar(); }, 100);
                });
            }

            conectarBuscadorCliente('cliente_buscador_filtro', 'resultados_cliente_filtro', 'cliente_id_filtro');
            conectarBuscadorCliente('cliente_buscador_modal', 'resultados_cliente_modal', 'cliente_id_modal');

            const btnAbrir = document.getElementById('btn_abrir_pago_credito');
            const btnCerrar = document.getElementById('btn_cerrar_pago_credito');
            const btnCancelar = document.getElementById('btn_cancelar_pago_credito');
            const modal = document.getElementById('modal_pago_credito');

            btnAbrir.addEventListener('click', function () {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
            function cerrar() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            btnCerrar.addEventListener('click', cerrar);
            btnCancelar.addEventListener('click', cerrar);
            modal.addEventListener('click', function (evento) {
                if (evento.target === modal) cerrar();
            });
        })();

        (function () {
            const btnVer = document.getElementById('btn_ver_pagos_cliente');

            if (!btnVer) {
                return;
            }

            const modal = document.getElementById('modal_pagos_cliente');
            const btnCerrar = document.getElementById('btn_cerrar_pagos_cliente');

            btnVer.addEventListener('click', function () {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });

            function cerrar() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            btnCerrar.addEventListener('click', cerrar);
            modal.addEventListener('click', function (evento) {
                if (evento.target === modal) cerrar();
            });
        })();

        (function () {
            let seleccion = new Set();

            const bloque = document.getElementById('bloque_seleccion_fiadas');
            const contador = document.getElementById('contador_seleccion_fiadas');

            function actualizarBloque() {
                contador.textContent = seleccion.size;
                bloque.classList.toggle('hidden', seleccion.size === 0);
            }

            // Cada checkbox lleva el `cliente_id`, no el id de la venta — si
            // el mismo cliente aparece en varias filas (varias ventas
            // fiadas), tildar cualquiera de ellas alcanza (el Set dedupe
            // solo) y la fecha se termina asignando una sola vez por cliente.
            document.querySelectorAll('.seleccion-fiada').forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    if (checkbox.checked) {
                        seleccion.add(checkbox.dataset.id);
                    } else {
                        seleccion.delete(checkbox.dataset.id);
                    }
                    actualizarBloque();
                });
            });

            document.getElementById('vaciar_seleccion_fiadas').addEventListener('click', function (evento) {
                evento.preventDefault();
                seleccion = new Set();
                document.querySelectorAll('.seleccion-fiada').forEach(function (cb) { cb.checked = false; });
                actualizarBloque();
            });

            document.getElementById('form_promesa_masiva').addEventListener('submit', function (evento) {
                if (seleccion.size === 0) {
                    evento.preventDefault();
                    return;
                }

                const contenedor = document.getElementById('ids_seleccionados_fiadas');
                contenedor.innerHTML = '';

                seleccion.forEach(function (id) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'cliente_ids[]';
                    input.value = id;
                    contenedor.appendChild(input);
                });
            });
        })();
    </script>
@endsection
