@extends('layouts.app')

@section('titulo', 'Ventas por producto')

@section('contenido')
    <div class="mb-4">
        <a href="{{ route('ventas.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Volver a Ventas</a>
    </div>

    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <form method="GET" action="{{ route('ventas.reporteProducto') }}" class="flex flex-wrap items-end gap-2">
            <div class="relative">
                <label for="buscador_producto" class="block text-xs text-slate-500 mb-1">Producto</label>
                <input type="text" id="buscador_producto" autocomplete="off" placeholder="Buscar por nombre o código..."
                    value="{{ $producto ? $producto->nombre.($producto->codigo ? ' ('.$producto->codigo.')' : '') : '' }}"
                    class="w-64 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                <input type="hidden" name="producto_id" id="producto_id" value="{{ $productoId }}">
                <ul id="resultados_producto" class="hidden absolute z-10 mt-1 w-64 max-h-48 overflow-auto rounded border border-slate-200 bg-white shadow-lg"></ul>
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
            <input type="hidden" name="por_pagina" value="{{ $porPagina }}">
            <button type="submit" class="inline-flex items-center gap-1.5 rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m20 20-3.5-3.5"/>
                </svg>
                Buscar
            </button>
            @if ($productoId || $fechaFiltradaManualmente)
                <a href="{{ route('ventas.reporteProducto') }}" class="text-sm text-slate-500 hover:underline pb-2">
                    Limpiar
                </a>
            @endif
        </form>
    </div>

    @if (! $productoId)
        <p class="text-sm text-slate-500">Elegí un producto para ver en qué ventas se vendió.</p>
    @elseif (! $producto)
        <p class="text-sm text-red-600">No se encontró ese producto.</p>
    @else
        <div class="bg-white rounded-lg shadow p-4 mb-4 flex items-center justify-between">
            <div>
                <p class="font-medium">{{ $producto->nombre }}</p>
                @if ($producto->codigo)
                    <p class="text-sm text-slate-500">Código: {{ $producto->codigo }}</p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-sm text-slate-500">Total vendido</p>
                <p class="font-medium">{{ (int) $totales->cantidad_total }} unidades — ${{ number_format($totales->importe_total, 2, ',', '.') }}</p>
            </div>
        </div>

        <div class="mb-4 flex justify-end">
            <form method="GET" action="{{ route('ventas.reporteProducto') }}" class="flex items-center gap-2 text-sm">
                <input type="hidden" name="producto_id" value="{{ $productoId }}">
                <input type="hidden" name="fecha_desde" value="{{ $fechaDesde }}">
                <input type="hidden" name="fecha_hasta" value="{{ $fechaHasta }}">
                <label for="por_pagina_select" class="text-slate-500">Mostrar</label>
                <select id="por_pagina_select" name="por_pagina" onchange="this.form.submit()"
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
                        <th class="px-4 py-2 font-medium">Fecha</th>
                        <th class="px-4 py-2 font-medium">Venta N°</th>
                        <th class="px-4 py-2 font-medium text-right">Cantidad</th>
                        <th class="px-4 py-2 font-medium text-right">Precio unitario</th>
                        <th class="px-4 py-2 font-medium text-right">Subtotal</th>
                        <th class="px-4 py-2 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($items as $item)
                        <tr>
                            <td class="px-4 py-2 text-slate-500">{{ $item->pedido->cobrado_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2 font-medium">{{ $item->pedido->numero_venta }}</td>
                            <td class="px-4 py-2 text-right">{{ $item->cantidad }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format($item->precio_unitario, 2, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format($item->subtotal, 2, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right">
                                <button type="button" data-abrir-detalle-venta="{{ $item->id }}" class="text-sm text-slate-600 hover:underline">
                                    Ver detalle
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-slate-500">No se vendió este producto en el rango elegido.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $items->links() }}
        </div>

        {{-- Detalle de cada venta pre-renderizado y oculto: el modal solo copia
             este HTML al abrirse, sin pedir nada al servidor, así no se pierde
             la página de paginación ni la búsqueda al ver el detalle. --}}
        <div class="hidden">
            @foreach ($items as $item)
                <div id="detalle_venta_{{ $item->id }}">
                    <p class="text-sm text-slate-500 mb-1">
                        Venta N° {{ $item->pedido->numero_venta }} — {{ $item->pedido->cobrado_at?->format('d/m/Y H:i') }}
                    </p>
                    <p class="text-sm mb-1">
                        Vendedor: {{ $item->pedido->vendedor->name }} · Cajero: {{ $item->pedido->cajero->name ?? '—' }}
                    </p>
                    <p class="text-sm mb-3">
                        Comprobante: {{ $item->pedido->tipo_comprobante === 'factura' ? 'Factura '.$item->pedido->factura?->tipo_factura : 'Remito' }}
                        · Medio de pago: {{ ucfirst($item->pedido->forma_pago ?? '—') }}
                    </p>
                    <table class="w-full text-sm mb-3">
                        <thead class="text-slate-500 text-left">
                            <tr>
                                <th class="py-1 font-medium">Producto</th>
                                <th class="py-1 font-medium text-right">Cant.</th>
                                <th class="py-1 font-medium text-right">Precio</th>
                                <th class="py-1 font-medium text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($item->pedido->items as $lineaItem)
                                <tr @class(['font-semibold' => $lineaItem->producto_id === $producto->id])>
                                    <td class="py-1">{{ $lineaItem->nombre_producto }}</td>
                                    <td class="py-1 text-right">{{ $lineaItem->cantidad }}</td>
                                    <td class="py-1 text-right">${{ number_format($lineaItem->precio_unitario, 2, ',', '.') }}</td>
                                    <td class="py-1 text-right">${{ number_format($lineaItem->subtotal, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="text-right font-medium mb-3">Total cobrado: ${{ number_format($item->pedido->total_cobrado, 2, ',', '.') }}</p>
                    <a href="{{ route('ventas.show', $item->pedido) }}" class="text-sm text-slate-600 hover:underline">Ver venta completa &rarr;</a>
                </div>
            @endforeach
        </div>

        <div id="modal_detalle_venta" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-auto">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-semibold">Detalle de la venta</h2>
                    <button type="button" id="modal_detalle_venta_cerrar" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
                </div>
                <div id="modal_detalle_venta_contenido"></div>
            </div>
        </div>
    @endif

    <script>
        (function () {
            const productos = @json($productosParaBuscador);
            const input = document.getElementById('buscador_producto');
            const hidden = document.getElementById('producto_id');
            const lista = document.getElementById('resultados_producto');

            let resultados = [];
            let indiceActivo = -1;

            function etiqueta(producto) {
                return producto.nombre + (producto.codigo ? ' (' + producto.codigo + ')' : '');
            }

            function renderizar() {
                lista.innerHTML = '';

                if (resultados.length === 0) {
                    lista.classList.add('hidden');
                    return;
                }

                resultados.forEach(function (producto, indice) {
                    const li = document.createElement('li');
                    li.textContent = etiqueta(producto);
                    li.className = 'px-3 py-2 text-sm cursor-pointer' + (indice === indiceActivo ? ' bg-slate-100' : '');
                    li.addEventListener('mousedown', function (evento) {
                        evento.preventDefault();
                        seleccionar(producto);
                    });
                    lista.appendChild(li);
                });

                lista.classList.remove('hidden');
            }

            function seleccionar(producto) {
                hidden.value = producto.id;
                input.value = etiqueta(producto);
                resultados = [];
                indiceActivo = -1;
                renderizar();
            }

            input.addEventListener('input', function () {
                hidden.value = '';
                const texto = input.value.trim().toLowerCase();
                indiceActivo = -1;

                resultados = texto === '' ? [] : productos.filter(function (producto) {
                    return producto.nombre.toLowerCase().includes(texto)
                        || (producto.codigo && producto.codigo.toLowerCase().includes(texto));
                }).slice(0, 8);

                renderizar();
            });

            input.addEventListener('keydown', function (evento) {
                if (evento.key === 'Enter') {
                    // Mismo criterio que el buscador de productos del carrito:
                    // resaltado con flechas, o único resultado posible, se
                    // selecciona. Ambiguo (0 o 2+ sin resaltar) no hace nada.
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
                setTimeout(function () {
                    resultados = [];
                    renderizar();
                }, 100);
            });

            // --- modal de detalle de venta (copia HTML ya renderizado, sin pedir nada al servidor) ---
            const modalDetalle = document.getElementById('modal_detalle_venta');

            if (modalDetalle) {
                const contenidoDetalle = document.getElementById('modal_detalle_venta_contenido');
                const cerrarDetalle = document.getElementById('modal_detalle_venta_cerrar');

                document.querySelectorAll('[data-abrir-detalle-venta]').forEach(function (boton) {
                    boton.addEventListener('click', function () {
                        const origen = document.getElementById('detalle_venta_' + boton.dataset.abrirDetalleVenta);
                        contenidoDetalle.innerHTML = origen.innerHTML;
                        modalDetalle.classList.remove('hidden');
                        modalDetalle.classList.add('flex');
                    });
                });

                cerrarDetalle.addEventListener('click', function () {
                    modalDetalle.classList.add('hidden');
                    modalDetalle.classList.remove('flex');
                });
            }
        })();
    </script>
@endsection
