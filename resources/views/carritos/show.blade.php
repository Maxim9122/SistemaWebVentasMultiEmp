@extends('layouts.app')

@section('titulo', 'Carrito')

@section('contenido')
    <div class="mb-4 flex gap-2 flex-wrap items-center">
        <form method="POST" action="{{ route('carritos.renombrar', $pedido) }}" class="flex items-center gap-1 rounded bg-slate-900 pl-3 pr-1.5 py-1">
            @csrf
            @method('PUT')
            <input type="text" name="cliente_nombre" value="{{ $pedido->cliente_nombre }}" placeholder="Nombre del cliente" maxlength="255"
                class="bg-transparent text-white text-sm placeholder-slate-400 border-0 p-0 w-32 focus:ring-0 focus:outline-none">
            <button type="submit" title="Guardar nombre" class="text-slate-300 hover:text-white text-sm leading-none px-1">✓</button>
        </form>
        @foreach ($otrosCarritos as $otro)
            <a href="{{ route('carritos.show', $otro) }}" class="rounded px-3 py-1.5 text-sm bg-white border border-slate-200 text-slate-600 hover:border-slate-400">
                {{ $otro->cliente_nombre ?: 'Carrito #'.$otro->id }}
            </a>
        @endforeach
        @if (auth()->user()->empresa->permite_multiples_carritos)
            <form method="POST" action="{{ route('carritos.store') }}">
                @csrf
                <button type="submit" title="Agregar otro carrito"
                    class="w-8 h-8 flex items-center justify-center rounded text-base font-semibold bg-white border border-slate-200 text-slate-600 hover:border-slate-400">
                    +
                </button>
            </form>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden mb-4">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">Producto</th>
                    <th class="px-4 py-2 font-medium">Cantidad{{ $puedeCambiarPrecio ? ' / Precio' : '' }}</th>
                    <th class="px-4 py-2 font-medium text-right">Subtotal</th>
                    <th class="px-4 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($pedido->items as $item)
                    <tr>
                        <td class="px-4 py-2">
                            {{ $item->nombre_producto }}
                            @if ($item->tienePrecioEspecial())
                                <span class="block text-xs text-amber-600">
                                    Precio especial (catálogo: ${{ number_format($item->precio_original, 2, ',', '.') }})
                                </span>
                            @elseif ($item->tienePrecioPorCantidad())
                                @php $tramo = $item->producto?->precioParaCantidad($item->cantidad); @endphp
                                @if ($tramo && $tramo['cantidad_minima'])
                                    <span class="block text-xs text-sky-600">
                                        Precio por cantidad desde {{ $tramo['cantidad_minima'] }} unidades (catálogo: ${{ number_format($item->precio_original, 2, ',', '.') }})
                                    </span>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            <form method="POST" action="{{ route('carritos.items.update', [$pedido, $item]) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PUT')
                                <input type="number" name="cantidad" value="{{ $item->cantidad }}" data-valor-guardado="{{ $item->cantidad }}" min="1"
                                    class="item-cantidad w-16 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                                @if ($puedeCambiarPrecio)
                                    <input type="number" name="precio_unitario"
                                        value="{{ $item->precio_manual ? $item->precio_unitario : '' }}"
                                        data-valor-guardado="{{ $item->precio_manual ? $item->precio_unitario : '' }}"
                                        placeholder="Auto: ${{ number_format($item->precio_unitario, 2, ',', '.') }}" step="0.01" min="0"
                                        class="item-precio w-28 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                                @endif
                                <button type="submit" class="text-xs text-slate-500 hover:underline whitespace-nowrap">Actualizar</button>
                            </form>
                        </td>
                        <td class="px-4 py-2 text-right">${{ number_format($item->subtotal, 2, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right">
                            <form method="POST" action="{{ route('carritos.items.destroy', [$pedido, $item]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:underline">Quitar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-slate-500">Todavía no agregaste productos.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <form id="form_agregar_producto" method="POST" action="{{ route('carritos.items.store', $pedido) }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div class="relative flex-1 min-w-[220px]">
                <label for="buscador_producto" class="block text-sm font-medium mb-1">Producto</label>
                <input type="text" id="buscador_producto" autocomplete="off" autofocus placeholder="Escribí para buscar..."
                    class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                <input type="hidden" name="producto_id" id="producto_id_seleccionado">
                <ul id="resultados_producto" class="hidden absolute z-10 mt-1 w-full max-h-60 overflow-auto rounded border border-slate-200 bg-white shadow-lg"></ul>
            </div>
            <div>
                <label for="cantidad" class="block text-sm font-medium mb-1">Cantidad</label>
                <input id="cantidad" name="cantidad" type="number" min="1" value="1" required
                    class="w-20 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            </div>
            @if ($puedeCambiarPrecio)
                <div>
                    <label for="precio_unitario" class="block text-sm font-medium mb-1">Precio especial</label>
                    <input id="precio_unitario" name="precio_unitario" type="number" step="0.01" min="0" placeholder="Catálogo"
                        class="w-28 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
            @endif
            <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                Agregar
            </button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-4 mb-4 flex items-center justify-end">
        <p class="text-lg font-semibold">Total: ${{ number_format($pedido->total, 2, ',', '.') }}</p>
    </div>

    <div class="bg-white rounded-lg shadow p-4 flex flex-wrap items-start justify-between gap-4">
        <form method="POST" action="{{ route('carritos.cerrar', $pedido) }}" id="form_cerrar_carrito" class="flex-1 min-w-[280px]">
            @csrf
            <div class="flex flex-wrap items-end gap-3 mb-4">
                <div>
                    <label for="destino" class="block text-sm font-medium mb-1">Cerrar carrito</label>
                    <select id="destino" name="destino" required
                        onchange="document.getElementById('fecha_programada_wrap').classList.toggle('hidden', this.value !== 'programado'); document.getElementById('cobro_wrap')?.classList.toggle('hidden', this.value !== 'inmediato')"
                        class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                        @if (auth()->user()->role === 'cajero_vendedor')
                            <option value="inmediato">Cobrar ahora</option>
                        @else
                            <option value="inmediato">Para ahora (va a caja)</option>
                        @endif
                        <option value="programado">Para otro día</option>
                    </select>
                </div>
                <div id="fecha_programada_wrap" class="hidden">
                    <label for="fecha_programada" class="block text-sm font-medium mb-1">Fecha</label>
                    <input type="date" id="fecha_programada" name="fecha_programada"
                        class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
            </div>

            @if (auth()->user()->role === 'cajero_vendedor')
                <div id="cobro_wrap" class="max-w-xl mb-4">
                    @include('partials.formulario-cobro', ['pedido' => $pedido, 'empresa' => $empresa, 'clientes' => $clientes])
                </div>
            @endif

            <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                Confirmar Compra
            </button>
        </form>

        <form method="POST" action="{{ route('carritos.cancelar', $pedido) }}">
            @csrf
            <button type="submit" class="rounded px-4 py-2 text-sm font-medium text-red-600 hover:underline">
                Cancelar carrito
            </button>
        </form>
    </div>

    @php
        $productosParaBuscador = $productos->map(function ($producto) {
            return [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'codigo' => $producto->codigo,
                'precio' => (float) $producto->precio,
            ];
        })->values();
    @endphp

    <script>
        (function () {
            const productos = @json($productosParaBuscador);

            const input = document.getElementById('buscador_producto');
            const hidden = document.getElementById('producto_id_seleccionado');
            const lista = document.getElementById('resultados_producto');
            const cantidadInput = document.getElementById('cantidad');
            const form = document.getElementById('form_agregar_producto');

            let resultados = [];
            let indiceActivo = -1;

            function formatearPrecio(precio) {
                return precio.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function renderizar() {
                lista.innerHTML = '';

                if (resultados.length === 0) {
                    lista.classList.add('hidden');
                    return;
                }

                resultados.forEach(function (producto, indice) {
                    const li = document.createElement('li');
                    li.textContent = producto.nombre + (producto.codigo ? ' (' + producto.codigo + ')' : '') + ' — $' + formatearPrecio(producto.precio);
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
                input.value = producto.nombre;
                resultados = [];
                indiceActivo = -1;
                renderizar();

                if (cantidadInput) {
                    cantidadInput.focus();
                    cantidadInput.select();
                }
            }

            input.addEventListener('input', function () {
                hidden.value = '';
                const texto = input.value.trim().toLowerCase();
                indiceActivo = -1;

                if (texto === '') {
                    resultados = [];
                    renderizar();
                    return;
                }

                resultados = productos.filter(function (producto) {
                    return producto.nombre.toLowerCase().includes(texto)
                        || (producto.codigo && producto.codigo.toLowerCase().includes(texto));
                }).slice(0, 8);

                renderizar();
            });

            input.addEventListener('keydown', function (evento) {
                if (evento.key === 'Enter') {
                    const texto = input.value.trim().toLowerCase();

                    // Coincidencia exacta de código (lector de código de barras o el código
                    // completo tipeado a mano) tiene prioridad sobre lo resaltado en la lista.
                    // Nunca se agrega solo al carrito acá: este Enter selecciona el producto y
                    // salta el foco a Cantidad — recién un segundo Enter ahí (submit nativo del
                    // form) lo agrega, dando tiempo a cambiar la cantidad por defecto.
                    const productoEscaneado = productos.find(function (producto) {
                        return producto.codigo && producto.codigo.toLowerCase() === texto;
                    });

                    const elegido = productoEscaneado
                        || (indiceActivo >= 0 ? resultados[indiceActivo] : (resultados.length === 1 ? resultados[0] : null));

                    if (elegido) {
                        evento.preventDefault();
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
                } else if (evento.key === 'Escape') {
                    resultados = [];
                    renderizar();
                }
            });

            input.addEventListener('blur', function () {
                setTimeout(function () {
                    resultados = [];
                    renderizar();
                }, 100);
            });

            form.addEventListener('submit', function (evento) {
                if (! hidden.value) {
                    evento.preventDefault();
                    input.focus();
                }
            });
        })();

        (function () {
            const formCerrar = document.getElementById('form_cerrar_carrito');

            if (! formCerrar) {
                return;
            }

            formCerrar.addEventListener('submit', function (evento) {
                const inputsConCambios = document.querySelectorAll('.item-cantidad, .item-precio');

                for (const campo of inputsConCambios) {
                    if (campo.value !== campo.dataset.valorGuardado) {
                        evento.preventDefault();
                        alert('Tenés cambios de cantidad o precio sin guardar. Apretá "Actualizar" en esa línea antes de confirmar la compra.');
                        campo.focus();
                        return;
                    }
                }
            });
        })();
    </script>
@endsection
