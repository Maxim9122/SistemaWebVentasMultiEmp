@extends('layouts.app')

@section('titulo', 'Presupuesto #'.$pedido->numero_presupuesto)

@section('contenido')
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('presupuestos.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Volver a Presupuestos</a>
        <form method="POST" action="{{ route('presupuestos.cancelar', $pedido) }}" onsubmit="return confirm('¿Cancelar este presupuesto? No se puede deshacer.');">
            @csrf
            <button type="submit" class="text-sm text-red-600 hover:underline">Cancelar presupuesto</button>
        </form>
    </div>

    {{-- Cliente --}}
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <p class="font-medium mb-3">Cliente</p>

        @if ($pedido->cliente)
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="text-sm">
                    <p class="font-medium">{{ $pedido->cliente->nombre }}</p>
                    <p class="text-slate-500">
                        @if ($pedido->cliente->cuit) CUIT: {{ $pedido->cliente->cuit }} @endif
                        @if ($pedido->cliente->telefono) · Tel: {{ $pedido->cliente->telefono }} @endif
                        @if ($pedido->cliente->email) · {{ $pedido->cliente->email }} @endif
                    </p>
                </div>
                <button type="button" id="btn_cambiar_cliente" class="text-sm text-slate-600 hover:underline">Cambiar</button>
            </div>
        @else
            <p class="text-sm text-slate-500 mb-2">Todavía no elegiste un cliente para este presupuesto (queda como "{{ \App\Models\Pedido::CLIENTE_POR_DEFECTO }}").</p>
        @endif

        <form method="POST" action="{{ route('presupuestos.cliente.update', $pedido) }}" id="form_cliente" class="{{ $pedido->cliente ? 'hidden' : '' }} mt-3 space-y-3">
            @csrf
            @method('PUT')

            <div class="relative">
                <label for="buscador_cliente" class="block text-sm font-medium mb-1">Buscar cliente existente</label>
                <input type="text" id="buscador_cliente" autocomplete="off" placeholder="Buscar por nombre o CUIT..."
                    class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                <input type="hidden" name="cliente_id" id="cliente_id_seleccionado">
                <ul id="resultados_cliente" class="hidden absolute z-10 mt-1 w-full max-w-md max-h-48 overflow-auto rounded border border-slate-200 bg-white shadow-lg"></ul>
                <button type="button" id="toggle_nuevo_cliente" class="text-sm text-slate-600 hover:underline mt-1">+ Cargar cliente nuevo</button>
            </div>

            <div id="form_nuevo_cliente" class="hidden grid grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Nombre</label>
                    <input type="text" name="cliente_nombre" id="cliente_nombre" class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">CUIT</label>
                    <input type="text" name="cliente_cuit" id="cliente_cuit" class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Teléfono</label>
                    <input type="text" name="cliente_telefono" id="cliente_telefono" placeholder="549..." class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Email</label>
                    <input type="email" name="cliente_email" id="cliente_email" class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
            </div>

            <button type="submit" class="rounded px-3 py-1.5 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                Guardar cliente
            </button>
            @if ($pedido->cliente)
                <button type="button" id="btn_cancelar_cambiar_cliente" class="text-sm text-slate-500 hover:underline">Cancelar</button>
            @endif
        </form>
    </div>

    {{-- Ítems --}}
    <div class="bg-white rounded-lg shadow overflow-hidden mb-4">
        <div class="overflow-x-auto">
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
                            @if ($item->producto?->esPorPeso())
                                <span class="block text-xs text-amber-600">
                                    Pesado — ${{ number_format($item->precio_original, 2, ',', '.') }} el kg
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            <form method="POST" action="{{ route('presupuestos.items.update', [$pedido, $item]) }}" class="flex items-center gap-2">
                                @csrf
                                @method('PUT')
                                @if ($item->producto?->esPorPeso())
                                    <input type="hidden" name="cantidad" value="1">
                                    <input type="number" name="precio_unitario" value="{{ $item->precio_unitario }}"
                                        step="0.01" min="0.01" required
                                        class="w-28 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                                @else
                                    <input type="number" name="cantidad" value="{{ $item->cantidad }}" min="1"
                                        class="w-16 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                                    @if ($puedeCambiarPrecio)
                                        <input type="number" name="precio_unitario"
                                            value="{{ $item->precio_manual ? $item->precio_unitario : '' }}"
                                            placeholder="Auto: ${{ number_format($item->precio_unitario, 2, ',', '.') }}" step="0.01" min="0"
                                            class="w-28 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                                    @endif
                                @endif
                                <button type="submit" class="text-xs text-slate-500 hover:underline whitespace-nowrap">Actualizar</button>
                            </form>
                        </td>
                        <td class="px-4 py-2 text-right">${{ number_format($item->subtotal, 2, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right">
                            <form method="POST" action="{{ route('presupuestos.items.destroy', [$pedido, $item]) }}">
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
    </div>

    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <form id="form_agregar_producto" method="POST" action="{{ route('presupuestos.items.store', $pedido) }}" class="flex flex-wrap items-end gap-3"
            data-puede-cambiar-precio="{{ $puedeCambiarPrecio ? '1' : '0' }}">
            @csrf
            <div class="relative flex-1 min-w-[220px]">
                <label for="buscador_producto" class="block text-sm font-medium mb-1">Producto</label>
                <input type="text" id="buscador_producto" autocomplete="off" autofocus placeholder="Escribí para buscar..."
                    class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                <input type="hidden" name="producto_id" id="producto_id_seleccionado">
                <ul id="resultados_producto" class="hidden absolute z-10 mt-1 w-full max-h-60 overflow-auto rounded border border-slate-200 bg-white shadow-lg"></ul>
            </div>
            <div id="campo_cantidad">
                <label for="cantidad" class="block text-sm font-medium mb-1">Cantidad</label>
                <input id="cantidad" name="cantidad" type="number" min="1" value="1" required
                    class="w-20 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            </div>
            <div id="campo_precio_unitario" class="{{ $puedeCambiarPrecio ? '' : 'hidden' }}">
                <label id="label_precio_unitario" for="precio_unitario" class="block text-sm font-medium mb-1">Precio especial</label>
                <input id="precio_unitario" name="precio_unitario" type="number" step="0.01" min="0" placeholder="Catálogo"
                    class="w-28 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                <p id="ayuda_precio_kg" class="hidden text-xs text-slate-500 mt-1"></p>
            </div>
            <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                Agregar
            </button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-4 mb-4 flex items-center justify-end">
        <p class="text-lg font-semibold">Total: ${{ number_format($pedido->total, 2, ',', '.') }}</p>
    </div>

    {{-- Enviar --}}
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <p class="font-medium mb-3">Enviar presupuesto</p>
        <div class="flex flex-wrap items-end gap-3">
            <form method="POST" action="{{ route('presupuestos.enviarEmail', $pedido) }}" class="flex items-end gap-2">
                @csrf
                <div>
                    <label for="email_envio" class="block text-xs text-slate-500 mb-1">Email del cliente</label>
                    <input type="email" name="email" id="email_envio" value="{{ old('email', $pedido->cliente?->email) }}" required
                        class="w-56 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
                <button type="submit" class="rounded px-3 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                    Enviar por email
                </button>
            </form>

            <div class="flex items-end gap-2">
                <div>
                    <label for="telefono_whatsapp" class="block text-xs text-slate-500 mb-1">Teléfono (WhatsApp, Argentina)</label>
                    <div class="flex items-center rounded border border-slate-300 focus-within:border-slate-500 focus-within:ring-1 focus-within:ring-slate-500">
                        <span class="pl-2 pr-1 text-sm text-slate-500 select-none">+54 9</span>
                        <input type="text" id="telefono_whatsapp" inputmode="numeric" maxlength="10"
                            value="{{ $pedido->cliente?->telefonoSoloDigitos() }}" placeholder="1123456789"
                            class="w-28 rounded-r border-0 text-sm py-2 focus:ring-0">
                    </div>
                </div>
                <button type="button" id="btn_whatsapp" class="rounded px-3 py-2 text-sm font-medium text-emerald-700 border border-emerald-300 hover:border-emerald-400">
                    Enviar por WhatsApp
                </button>
            </div>

            <a href="{{ route('presupuestos.imprimirTicket', $pedido) }}" target="_blank" class="text-sm text-slate-600 hover:underline pb-2">
                Ver / imprimir presupuesto
            </a>
        </div>
    </div>

    {{-- Cobrar --}}
    <div class="bg-white rounded-lg shadow p-4">
        <p class="font-medium mb-4">Cobrar presupuesto</p>
        <form method="POST" action="{{ route('presupuestos.cobrar', $pedido) }}">
            @csrf

            @include('partials.formulario-cobro', ['pedido' => $pedido, 'empresa' => $empresa, 'clientes' => $clientes])

            <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                Confirmar cobro
            </button>
        </form>
    </div>

    @php
        $clientesParaBuscador = $clientes->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'cuit' => $c->cuit])->values();
    @endphp

    <script>
        (function () {
            const urlBuscarProducto = {{ Js::from(route('presupuestos.buscarProducto')) }};

            const input = document.getElementById('buscador_producto');
            const hidden = document.getElementById('producto_id_seleccionado');
            const lista = document.getElementById('resultados_producto');
            const cantidadInput = document.getElementById('cantidad');
            const campoCantidad = document.getElementById('campo_cantidad');
            const campoPrecioUnitario = document.getElementById('campo_precio_unitario');
            const labelPrecioUnitario = document.getElementById('label_precio_unitario');
            const inputPrecioUnitario = document.getElementById('precio_unitario');
            const ayudaPrecioKg = document.getElementById('ayuda_precio_kg');
            const form = document.getElementById('form_agregar_producto');
            const puedeCambiarPrecio = form.dataset.puedeCambiarPrecio === '1';

            let resultados = [];
            let indiceActivo = -1;

            let timerDebounce = null;
            let idPeticion = 0;

            function formatearPrecio(precio) {
                return precio.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            // Búsqueda en el servidor en vez de filtrar un catálogo entero ya
            // cargado en la página — con empresas de varios miles de
            // productos, mandar todo el catálogo en cada carga de esta
            // pantalla pesaba varios cientos de KB. idPeticion descarta la
            // respuesta si para cuando llega ya se tipeó algo más nuevo.
            function buscarEnServidor(texto) {
                const miId = ++idPeticion;

                return fetch(urlBuscarProducto + '?q=' + encodeURIComponent(texto), {
                    headers: { 'Accept': 'application/json' },
                })
                    .then((respuesta) => respuesta.json())
                    .then((datos) => (miId === idPeticion ? datos : null))
                    .catch(() => null);
            }

            // Un producto "por peso" (unidad = kg) ya viene con el precio
            // final calculado por una balanza — no tiene sentido pedir
            // cantidad, se pide directamente ese precio total. El campo
            // precio_unitario pasa a estar siempre visible y obligatorio
            // para este caso puntual, sin importar el permiso de "cambiar
            // precio de venta" (no es una excepción al precio, es la única
            // forma de vender este producto).
            function aplicarModoProducto(producto) {
                const esPorPeso = !!(producto && producto.es_por_peso);

                campoCantidad.classList.toggle('hidden', esPorPeso);
                campoPrecioUnitario.classList.toggle('hidden', ! esPorPeso && ! puedeCambiarPrecio);

                if (esPorPeso) {
                    cantidadInput.value = 1;
                    labelPrecioUnitario.textContent = 'Precio';
                    inputPrecioUnitario.placeholder = 'Total ya pesado';
                    inputPrecioUnitario.required = true;
                    inputPrecioUnitario.value = '';
                    ayudaPrecioKg.textContent = 'Precio por kg: $' + formatearPrecio(producto.precio);
                    ayudaPrecioKg.classList.remove('hidden');
                } else {
                    labelPrecioUnitario.textContent = 'Precio especial';
                    inputPrecioUnitario.placeholder = 'Catálogo';
                    inputPrecioUnitario.required = false;
                    ayudaPrecioKg.classList.add('hidden');
                }
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
                aplicarModoProducto(producto);

                if (producto.es_por_peso) {
                    inputPrecioUnitario.focus();
                } else if (cantidadInput) {
                    cantidadInput.focus();
                    cantidadInput.select();
                }
            }

            input.addEventListener('input', function () {
                hidden.value = '';
                const texto = input.value.trim();
                indiceActivo = -1;
                aplicarModoProducto(null);
                clearTimeout(timerDebounce);

                if (texto === '') {
                    resultados = [];
                    renderizar();
                    return;
                }

                // Debounce corto: no dispara una búsqueda por cada letra
                // tipeada, solo cuando se deja de escribir un instante.
                timerDebounce = setTimeout(function () {
                    buscarEnServidor(texto).then(function (datos) {
                        if (datos === null) {
                            return;
                        }

                        resultados = datos;
                        renderizar();
                    });
                }, 200);
            });

            input.addEventListener('keydown', function (evento) {
                if (evento.key === 'Enter') {
                    const texto = input.value.trim();

                    if (texto === '') {
                        return;
                    }

                    evento.preventDefault();
                    clearTimeout(timerDebounce);

                    // Nunca se agrega solo al carrito acá: este Enter busca y
                    // selecciona el producto, salta el foco a Cantidad (o a
                    // Precio si es por peso) — recién un segundo Enter ahí
                    // (submit nativo del form) lo agrega.
                    buscarEnServidor(texto).then(function (datos) {
                        if (datos === null) {
                            return;
                        }

                        resultados = datos;

                        const textoMin = texto.toLowerCase();
                        const productoEscaneado = resultados.find(function (producto) {
                            return producto.codigo && producto.codigo.toLowerCase() === textoMin;
                        });

                        const elegido = productoEscaneado
                            || (indiceActivo >= 0 ? resultados[indiceActivo] : (resultados.length === 1 ? resultados[0] : null));

                        if (elegido) {
                            seleccionar(elegido);
                        } else {
                            renderizar();
                        }
                    });

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
            const clientes = @json($clientesParaBuscador);

            const input = document.getElementById('buscador_cliente');
            const hidden = document.getElementById('cliente_id_seleccionado');
            const lista = document.getElementById('resultados_cliente');
            const toggleNuevo = document.getElementById('toggle_nuevo_cliente');
            const formNuevo = document.getElementById('form_nuevo_cliente');
            const btnCambiar = document.getElementById('btn_cambiar_cliente');
            const btnCancelarCambiar = document.getElementById('btn_cancelar_cambiar_cliente');
            const formCliente = document.getElementById('form_cliente');

            if (!input) {
                return;
            }

            let resultados = [];
            let indiceActivo = -1;

            function renderizar() {
                lista.innerHTML = '';

                if (resultados.length === 0) {
                    lista.classList.add('hidden');
                    return;
                }

                resultados.forEach(function (cliente, indice) {
                    const li = document.createElement('li');
                    li.textContent = cliente.nombre + (cliente.cuit ? ' (' + cliente.cuit + ')' : '');
                    li.className = 'px-3 py-2 text-sm cursor-pointer' + (indice === indiceActivo ? ' bg-slate-100' : '');
                    li.addEventListener('mousedown', function (evento) {
                        evento.preventDefault();
                        seleccionar(cliente);
                    });
                    lista.appendChild(li);
                });

                lista.classList.remove('hidden');
            }

            function seleccionar(cliente) {
                hidden.value = cliente.id;
                input.value = cliente.nombre + (cliente.cuit ? ' (' + cliente.cuit + ')' : '');
                resultados = [];
                indiceActivo = -1;
                renderizar();
            }

            input.addEventListener('input', function () {
                hidden.value = '';
                const texto = input.value.trim().toLowerCase();
                indiceActivo = -1;

                resultados = texto === '' ? [] : clientes.filter(function (cliente) {
                    return cliente.nombre.toLowerCase().includes(texto) || (cliente.cuit && cliente.cuit.toLowerCase().includes(texto));
                }).slice(0, 8);

                renderizar();
            });

            input.addEventListener('keydown', function (evento) {
                if (evento.key === 'Enter') {
                    // Mismo criterio que el buscador de productos: si hay uno
                    // resaltado con las flechas, o si el texto tipeado dejó un
                    // único resultado posible, Enter lo selecciona. Si es
                    // ambiguo (0 o 2+ sin resaltar), no hace nada — nunca deja
                    // pasar el Enter para que dispare el submit nativo del
                    // formulario con un cliente sin elegir.
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

            // Igual que el buscador de productos: si se llega a colar un
            // submit (ej. Enter en otro campo del form) sin haber elegido un
            // cliente existente NI estar cargando uno nuevo, se frena acá.
            // Antes de esto, ese caso mandaba el form vacío y el servidor
            // respondía "Cliente actualizado" sin haber asignado ninguno.
            formCliente.addEventListener('submit', function (evento) {
                const cargandoNuevo = !formNuevo.classList.contains('hidden');

                if (cargandoNuevo) {
                    return;
                }

                if (!hidden.value) {
                    evento.preventDefault();
                    input.focus();
                }
            });

            toggleNuevo.addEventListener('click', function () {
                const visible = !formNuevo.classList.contains('hidden');
                formNuevo.classList.toggle('hidden', visible);
                toggleNuevo.textContent = visible ? '+ Cargar cliente nuevo' : 'Buscar cliente existente';

                if (!visible) {
                    hidden.value = '';
                    input.value = '';
                }
            });

            if (btnCambiar) {
                btnCambiar.addEventListener('click', function () {
                    formCliente.classList.remove('hidden');
                });
            }

            if (btnCancelarCambiar) {
                btnCancelarCambiar.addEventListener('click', function () {
                    formCliente.classList.add('hidden');
                });
            }
        })();

        (function () {
            const btnWhatsapp = document.getElementById('btn_whatsapp');
            const inputTelefono = document.getElementById('telefono_whatsapp');
            const ticketUrl = {{ Js::from($ticketUrlFirmada) }};
            const numero = {{ Js::from($pedido->numero_presupuesto) }};

            inputTelefono.addEventListener('input', function () {
                inputTelefono.value = inputTelefono.value.replace(/\D/g, '').slice(0, 10);
            });

            btnWhatsapp.addEventListener('click', function () {
                const telefono = (inputTelefono.value || '').replace(/\D/g, '');

                if (telefono.length !== 10) {
                    alert('Ingresá los 10 dígitos del teléfono (sin 0 ni 15) para poder enviarlo por WhatsApp.');
                    inputTelefono.focus();
                    return;
                }

                const mensaje = 'Te comparto el presupuesto N° ' + numero + ': ' + ticketUrl;
                const url = 'https://wa.me/549' + telefono + '?text=' + encodeURIComponent(mensaje);

                window.open(url, '_blank');
            });
        })();
    </script>
@endsection
