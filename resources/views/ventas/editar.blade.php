@extends('layouts.app')

@section('titulo', 'Editar venta #'.$pedido->numero_venta)

@section('contenido')
    <a href="{{ route('ventas.show', $pedido) }}" class="text-sm text-slate-500 hover:underline">&larr; Volver a la venta</a>

    @if ($errors->any())
        <div class="mt-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($pedido->factura?->estaAprobada())
        <div class="mt-4 rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            @if ($pedido->factura->notaCredito)
                Esta venta ya tiene una <strong>Factura {{ $pedido->factura->tipo_factura }}</strong> aprobada (CAE {{ $pedido->factura->cae }}),
                que ya fue anulada con una nota de crédito. Como ese comprobante ya está acreditado, <strong>no se va a emitir otra
                nota de crédito</strong> — al guardar los cambios se va a generar directamente una <strong>factura nueva</strong>
                con los datos actualizados.
            @else
                Esta venta ya tiene una <strong>Factura {{ $pedido->factura->tipo_factura }}</strong> aprobada (CAE {{ $pedido->factura->cae }}).
                AFIP no permite corregir un comprobante ya emitido — antes de guardar, el sistema verifica que ese comprobante
                tenga un CAE válido y, si lo tiene, va a emitir una <strong>nota de crédito por el total de esa factura</strong>
                y va a generar una <strong>factura nueva</strong> con los datos actualizados.
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('ventas.update', $pedido) }}" id="form_editar_venta" class="mt-4">
        @csrf

        <div class="bg-white rounded-lg shadow overflow-hidden mb-4">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-2 font-medium">Producto</th>
                        <th class="px-4 py-2 font-medium">Cantidad</th>
                        @if ($puedeCambiarPrecio)
                            <th class="px-4 py-2 font-medium">Precio</th>
                        @endif
                        <th class="px-4 py-2 font-medium text-right">Subtotal</th>
                        <th class="px-4 py-2 font-medium"></th>
                    </tr>
                </thead>
                <tbody id="filas_items" class="divide-y">
                    @foreach ($pedido->items as $item)
                        <tr data-precio-auto="{{ $item->precio_unitario }}">
                            <td class="px-4 py-2">
                                {{ $item->nombre_producto }}
                                <input type="hidden" name="items[{{ $loop->index }}][producto_id]" value="{{ $item->producto_id }}" class="item-producto-id">
                            </td>
                            <td class="px-4 py-2">
                                <input type="number" name="items[{{ $loop->index }}][cantidad]" value="{{ $item->cantidad }}" min="1"
                                    class="item-cantidad w-20 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                            </td>
                            @if ($puedeCambiarPrecio)
                                <td class="px-4 py-2">
                                    <input type="number" name="items[{{ $loop->index }}][precio_unitario]"
                                        value="{{ $item->precio_manual ? $item->precio_unitario : '' }}"
                                        placeholder="Auto: ${{ number_format($item->precio_unitario, 2, ',', '.') }}" step="0.01" min="0"
                                        class="item-precio w-28 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                                </td>
                            @endif
                            <td class="item-subtotal px-4 py-2 text-right">${{ number_format($item->subtotal, 2, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right">
                                <button type="button" class="btn-quitar-fila text-sm text-red-600 hover:underline">Quitar</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-lg shadow p-4 mb-4 flex items-center justify-end">
            <p class="text-lg font-semibold">Total: <span id="total_pedido">${{ number_format($pedido->items->sum('subtotal'), 2, ',', '.') }}</span></p>
        </div>

        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <label for="buscador_producto" class="block text-sm font-medium mb-1">Agregar producto</label>
            <div class="relative max-w-md">
                <input type="text" id="buscador_producto" autocomplete="off" placeholder="Escribí para buscar..."
                    class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                <ul id="resultados_producto" class="hidden absolute z-10 mt-1 w-full max-h-60 overflow-auto rounded border border-slate-200 bg-white shadow-lg"></ul>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <label for="motivo" class="block text-sm font-medium mb-1">Motivo de la modificación (opcional)</label>
            <textarea id="motivo" name="motivo" rows="2" maxlength="500"
                class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">{{ old('motivo') }}</textarea>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                Continuar
            </button>
            <a href="{{ route('ventas.show', $pedido) }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                Cancelar
            </a>
        </div>
    </form>

    @php
        $productosParaBuscador = $productos->map(fn ($producto) => [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'codigo' => $producto->codigo,
            'precio' => (float) $producto->precio,
        ])->values();
    @endphp

    <script>
        (function () {
            const productos = @json($productosParaBuscador);
            const puedeCambiarPrecio = @json($puedeCambiarPrecio);

            const cuerpo = document.getElementById('filas_items');
            const input = document.getElementById('buscador_producto');
            const lista = document.getElementById('resultados_producto');
            const form = document.getElementById('form_editar_venta');
            const totalElemento = document.getElementById('total_pedido');

            let indice = {{ $pedido->items->count() }};
            let resultados = [];
            let indiceActivo = -1;

            function formatearPrecio(precio) {
                return precio.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            // El precio "auto" es aproximado (no recalcula tramos por cantidad
            // en vivo, solo para la vista previa) — el total real y definitivo
            // siempre lo calcula el servidor al guardar.
            function calcularSubtotalFila(fila) {
                const cantidadInput = fila.querySelector('.item-cantidad');
                const precioInput = fila.querySelector('.item-precio');
                const celdaSubtotal = fila.querySelector('.item-subtotal');
                const cantidad = parseFloat(cantidadInput?.value) || 0;

                let precio;
                if (precioInput && precioInput.value.trim() !== '') {
                    precio = parseFloat(precioInput.value) || 0;
                } else {
                    precio = parseFloat(fila.dataset.precioAuto) || 0;
                }

                const subtotal = cantidad * precio;

                if (celdaSubtotal) {
                    celdaSubtotal.textContent = '$' + formatearPrecio(subtotal);
                }

                return subtotal;
            }

            function recalcularTotal() {
                let total = 0;
                cuerpo.querySelectorAll('tr').forEach(function (fila) {
                    total += calcularSubtotalFila(fila);
                });
                totalElemento.textContent = '$' + formatearPrecio(total);
            }

            cuerpo.addEventListener('input', function (evento) {
                if (evento.target.matches('.item-cantidad, .item-precio')) {
                    recalcularTotal();
                }
            });

            function agregarFila(producto) {
                const fila = document.createElement('tr');
                fila.dataset.precioAuto = producto.precio;
                const celdaPrecio = puedeCambiarPrecio ? `
                    <td class="px-4 py-2">
                        <input type="number" name="items[${indice}][precio_unitario]"
                            placeholder="Auto: $${formatearPrecio(producto.precio)}" step="0.01" min="0"
                            class="item-precio w-28 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    </td>
                ` : '';
                fila.innerHTML = `
                    <td class="px-4 py-2">
                        ${producto.nombre}
                        <input type="hidden" name="items[${indice}][producto_id]" value="${producto.id}" class="item-producto-id">
                    </td>
                    <td class="px-4 py-2">
                        <input type="number" name="items[${indice}][cantidad]" value="1" min="1"
                            class="item-cantidad w-20 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    </td>
                    ${celdaPrecio}
                    <td class="item-subtotal px-4 py-2 text-right">$0,00</td>
                    <td class="px-4 py-2 text-right">
                        <button type="button" class="btn-quitar-fila text-sm text-red-600 hover:underline">Quitar</button>
                    </td>
                `;
                cuerpo.appendChild(fila);
                indice++;
                conectarBotonesQuitar();
                recalcularTotal();
            }

            function conectarBotonesQuitar() {
                document.querySelectorAll('.btn-quitar-fila').forEach(function (boton) {
                    boton.onclick = function () {
                        if (cuerpo.querySelectorAll('tr').length <= 1) {
                            alert('Una venta necesita al menos 1 producto.');
                            return;
                        }
                        boton.closest('tr').remove();
                        recalcularTotal();
                    };
                });
            }

            conectarBotonesQuitar();
            recalcularTotal();

            function renderizar() {
                lista.innerHTML = '';

                if (resultados.length === 0) {
                    lista.classList.add('hidden');
                    return;
                }

                resultados.forEach(function (producto, i) {
                    const li = document.createElement('li');
                    li.textContent = producto.nombre + (producto.codigo ? ' (' + producto.codigo + ')' : '') + ' — $' + formatearPrecio(producto.precio);
                    li.className = 'px-3 py-2 text-sm cursor-pointer' + (i === indiceActivo ? ' bg-slate-100' : '');
                    li.addEventListener('mousedown', function (evento) {
                        evento.preventDefault();
                        seleccionar(producto);
                    });
                    lista.appendChild(li);
                });

                lista.classList.remove('hidden');
            }

            function filaExistentePara(productoId) {
                return Array.from(cuerpo.querySelectorAll('tr')).find(function (fila) {
                    const idInput = fila.querySelector('.item-producto-id');
                    return idInput && parseInt(idInput.value, 10) === productoId;
                });
            }

            function seleccionar(producto) {
                // Si el producto ya está en la lista, suma la cantidad a esa misma
                // línea en vez de crear una duplicada — mismo criterio que el
                // carrito del vendedor.
                const filaExistente = filaExistentePara(producto.id);

                if (filaExistente) {
                    const cantidadInput = filaExistente.querySelector('.item-cantidad');
                    cantidadInput.value = (parseInt(cantidadInput.value, 10) || 0) + 1;
                    recalcularTotal();
                } else {
                    agregarFila(producto);
                }

                input.value = '';
                resultados = [];
                indiceActivo = -1;
                renderizar();
                input.focus();
            }

            input.addEventListener('input', function () {
                const texto = input.value.trim().toLowerCase();
                indiceActivo = -1;

                resultados = texto === '' ? [] : productos.filter(function (producto) {
                    return producto.nombre.toLowerCase().includes(texto)
                        || (producto.codigo && producto.codigo.toLowerCase().includes(texto));
                }).slice(0, 8);

                renderizar();
            });

            input.addEventListener('keydown', function (evento) {
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
                } else if (evento.key === 'Enter') {
                    evento.preventDefault();
                    const elegido = indiceActivo >= 0 ? resultados[indiceActivo] : (resultados.length === 1 ? resultados[0] : null);

                    if (elegido) {
                        seleccionar(elegido);
                    }
                }
            });

            input.addEventListener('blur', function () {
                setTimeout(function () {
                    resultados = [];
                    renderizar();
                }, 100);
            });

            form.addEventListener('submit', function (evento) {
                if (cuerpo.querySelectorAll('tr').length === 0) {
                    evento.preventDefault();
                    alert('Agregá al menos un producto.');
                }
            });
        })();
    </script>
@endsection
