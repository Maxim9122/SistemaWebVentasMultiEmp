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
            <button type="submit" class="inline-flex items-center gap-1.5 rounded px-3 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m20 20-3.5-3.5"/>
                </svg>
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

    @if (! $cliente && $totalesGenerales)
        <div class="bg-white rounded-lg shadow p-6 mb-4">
            <p class="text-sm text-slate-500 mb-3">Total de todos los clientes (histórico, sin filtrar por fecha)</p>
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-slate-500">Total fiado</p>
                    <p class="font-medium">${{ number_format($totalesGenerales['totalFiado'], 2, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Total pagado</p>
                    <p class="font-medium text-emerald-600">${{ number_format($totalesGenerales['totalPagado'], 2, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Saldo pendiente</p>
                    <p class="font-medium text-lg @class(['text-orange-600' => $totalesGenerales['saldoPendiente'] > 0])">
                        ${{ number_format($totalesGenerales['saldoPendiente'], 2, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if ($cliente)
        <div class="bg-white rounded-lg shadow p-6 mb-4">
            <div class="mb-4">
                <p class="text-sm text-slate-500 mb-1">Cliente</p>
                <p class="text-lg font-semibold">{{ $cliente->nombre }} @if ($cliente->cuit) <span class="text-sm font-normal text-slate-500">({{ $cliente->cuit }})</span> @endif</p>
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
    @endif

    {{-- Detalle de pagos: antes solo aparecía filtrando por cliente, ahora
    siempre se muestra, acotado al mismo rango de fechas que ya filtra la
    tabla de ventas fiadas (por defecto hoy) — y además por cliente si hay
    uno elegido, igual que antes. --}}
    <div class="bg-white rounded-lg shadow p-4 mb-4 flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-slate-600">
            Pagos @if ($cliente) de {{ $cliente->nombre }} @endif
            entre {{ \Illuminate\Support\Carbon::parse($fechaDesde)->format('d/m/Y') }} y {{ \Illuminate\Support\Carbon::parse($fechaHasta)->format('d/m/Y') }}
        </p>
        <button type="button" id="btn_ver_pagos_cliente" class="rounded px-3 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400 whitespace-nowrap">
            Ver detalle ({{ $pagosCliente->count() }})
        </button>
    </div>

    {{-- Modal: detalle de pagos parciales (del rango de fechas, y del cliente si hay uno filtrado) --}}
    <div id="modal_pagos_cliente" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[80vh] overflow-y-auto p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Historial de pagos{{ $cliente ? ' de '.$cliente->nombre : '' }}</h2>
                <button type="button" id="btn_cerrar_pagos_cliente" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-2 font-medium">Fecha y hora</th>
                        @unless ($cliente)
                            <th class="px-4 py-2 font-medium">Cliente</th>
                        @endunless
                        <th class="px-4 py-2 font-medium">Registrado por</th>
                        <th class="px-4 py-2 font-medium text-right">Efectivo</th>
                        <th class="px-4 py-2 font-medium text-right">Tarjeta</th>
                        <th class="px-4 py-2 font-medium text-right">Transferencia</th>
                        <th class="px-4 py-2 font-medium text-right">Total</th>
                        <th class="px-4 py-2 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($pagosCliente as $pago)
                        <tr @class(['opacity-50' => $pago->estaAnulado()])>
                            <td class="px-4 py-2 text-slate-500">{{ $pago->created_at->format('d/m/Y H:i') }}</td>
                            @unless ($cliente)
                                <td class="px-4 py-2">{{ $pago->cliente->nombre ?? '—' }}</td>
                            @endunless
                            <td class="px-4 py-2">{{ $pago->usuario->name }}</td>
                            <td class="px-4 py-2 text-right">{{ $pago->monto_efectivo > 0 ? '$'.number_format($pago->monto_efectivo, 2, ',', '.') : '—' }}</td>
                            <td class="px-4 py-2 text-right">{{ $pago->monto_tarjeta > 0 ? '$'.number_format($pago->monto_tarjeta, 2, ',', '.') : '—' }}</td>
                            <td class="px-4 py-2 text-right">{{ $pago->monto_transferencia > 0 ? '$'.number_format($pago->monto_transferencia, 2, ',', '.') : '—' }}</td>
                            <td class="px-4 py-2 text-right font-medium">${{ number_format($pago->total(), 2, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">
                                @if ($pago->estaAnulado())
                                    <span class="text-xs font-medium rounded px-2 py-1 bg-slate-200 text-slate-700">Anulado</span>
                                @else
                                    <a href="{{ route('creditos.pagos.comprobantePdf', $pago) }}" target="_blank" class="text-xs text-slate-500 hover:underline">
                                        Comprobante
                                    </a>
                                    <button type="button" class="btn-editar-pago text-xs text-slate-500 hover:underline ml-2"
                                        data-url="{{ route('creditos.pagos.update', $pago) }}"
                                        data-cliente="{{ $pago->cliente->nombre ?? '—' }}"
                                        data-efectivo="{{ $pago->monto_efectivo }}"
                                        data-tarjeta="{{ $pago->monto_tarjeta }}"
                                        data-transferencia="{{ $pago->monto_transferencia }}">
                                        Editar
                                    </button>
                                    <form method="POST" action="{{ route('creditos.pagos.anular', $pago) }}" class="inline"
                                        onsubmit="return confirm('¿Anular este pago? El saldo pendiente del cliente va a aumentar de nuevo. No se borra, queda marcado como anulado.');">
                                        @csrf
                                        <button type="submit" class="text-xs text-red-600 hover:underline ml-2">Anular</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $cliente ? 6 : 7 }}" class="px-4 py-6 text-center text-slate-500">No hay pagos que coincidan con el filtro.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    {{-- Modal: editar un pago parcial existente --}}
    <div id="modal_editar_pago" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">Editar pago de <span id="editar_pago_cliente"></span></h2>
                <button type="button" id="btn_cerrar_editar_pago" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>

            <form method="POST" id="form_editar_pago" class="space-y-3">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label for="editar_pago_efectivo" class="block text-sm font-medium mb-1">Efectivo</label>
                        <input type="number" id="editar_pago_efectivo" name="monto_efectivo" step="0.01" min="0"
                            class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <div>
                        <label for="editar_pago_tarjeta" class="block text-sm font-medium mb-1">Tarjeta</label>
                        <input type="number" id="editar_pago_tarjeta" name="monto_tarjeta" step="0.01" min="0"
                            class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <div>
                        <label for="editar_pago_transferencia" class="block text-sm font-medium mb-1">Transferencia</label>
                        <input type="number" id="editar_pago_transferencia" name="monto_transferencia" step="0.01" min="0"
                            class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                        Guardar cambios
                    </button>
                    <button type="button" id="btn_cancelar_editar_pago" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

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
                let indiceActivo = -1;

                function renderizar() {
                    lista.innerHTML = '';

                    if (resultados.length === 0) {
                        lista.classList.add('hidden');
                        return;
                    }

                    resultados.forEach(function (c, indice) {
                        const li = document.createElement('li');
                        li.textContent = c.nombre + (c.cuit ? ' (' + c.cuit + ')' : '');
                        li.className = 'px-3 py-2 text-sm cursor-pointer' + (indice === indiceActivo ? ' bg-slate-100' : ' hover:bg-slate-100');
                        li.addEventListener('mousedown', function (evento) {
                            evento.preventDefault();
                            seleccionar(c);
                        });
                        lista.appendChild(li);
                    });

                    lista.classList.remove('hidden');
                }

                function seleccionar(c) {
                    input.value = c.nombre + (c.cuit ? ' (' + c.cuit + ')' : '');
                    hidden.value = c.id;
                    resultados = [];
                    indiceActivo = -1;
                    renderizar();
                }

                input.addEventListener('input', function () {
                    hidden.value = '';
                    const texto = input.value.trim().toLowerCase();
                    indiceActivo = -1;
                    resultados = texto === '' ? [] : clientes.filter(function (c) {
                        return c.nombre.toLowerCase().includes(texto) || (c.cuit && c.cuit.toLowerCase().includes(texto));
                    }).slice(0, 8);
                    renderizar();
                });

                input.addEventListener('keydown', function (evento) {
                    if (evento.key === 'Enter') {
                        // Mismo criterio que el buscador de productos: resaltado
                        // con flechas, o único resultado posible, se selecciona.
                        const elegido = indiceActivo >= 0 ? resultados[indiceActivo] : (resultados.length === 1 ? resultados[0] : null);

                        evento.preventDefault();

                        if (elegido) {
                            seleccionar(elegido);
                        }

                        return;
                    }

                    if (resultados.length === 0) {
                        return;
                    }

                    if (evento.key === 'ArrowDown') {
                        evento.preventDefault();
                        indiceActivo = (indiceActivo + 1) % resultados.length;
                        renderizar();
                    } else if (evento.key === 'ArrowUp') {
                        evento.preventDefault();
                        indiceActivo = (indiceActivo - 1 + resultados.length) % resultados.length;
                        renderizar();
                    }
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
            const modal = document.getElementById('modal_editar_pago');
            const form = document.getElementById('form_editar_pago');
            const nombreCliente = document.getElementById('editar_pago_cliente');
            const campoEfectivo = document.getElementById('editar_pago_efectivo');
            const campoTarjeta = document.getElementById('editar_pago_tarjeta');
            const campoTransferencia = document.getElementById('editar_pago_transferencia');

            function cerrar() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            document.querySelectorAll('.btn-editar-pago').forEach(function (boton) {
                boton.addEventListener('click', function () {
                    form.action = boton.dataset.url;
                    nombreCliente.textContent = boton.dataset.cliente;
                    campoEfectivo.value = boton.dataset.efectivo;
                    campoTarjeta.value = boton.dataset.tarjeta;
                    campoTransferencia.value = boton.dataset.transferencia;

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    campoEfectivo.focus();
                });
            });

            document.getElementById('btn_cerrar_editar_pago').addEventListener('click', cerrar);
            document.getElementById('btn_cancelar_editar_pago').addEventListener('click', cerrar);
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
