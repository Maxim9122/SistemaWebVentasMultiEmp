@extends('layouts.app')

@section('titulo', 'Productos')

@section('contenido')
    <div class="mb-4 flex flex-wrap justify-end gap-2">
        <a href="{{ route('productos.proveedores.index') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            Proveedores
        </a>
        <a href="{{ route('productos.importaciones.index') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            Importaciones
        </a>
        <a href="{{ route('productos.reposiciones.index') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            Reposiciones
        </a>
        <button type="button" id="btn_abrir_reponer_stock" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            Reponer stock
        </button>
        <a href="{{ route('productos.grupos.index') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            Grupos
        </a>
        <a href="{{ route('productos.importar.subir') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            Importar desde Excel
        </a>
        <button type="button" id="btn_exportar_productos" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            Descargar Excel
        </button>
        <button type="button" id="btn_exportar_pdf_productos" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            Descargar PDF
        </button>
        <a href="{{ route('productos.create') }}" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
            + Nuevo producto
        </a>
    </div>

    @include('partials.modal-confirmacion', [
        'id' => 'modal_exportar_productos',
        'titulo' => 'Descargar Excel',
        'mensaje' => $buscar !== ''
            ? 'Se va a descargar un Excel con los productos que coinciden con "'.$buscar.'".'
            : 'Se va a descargar un Excel con los '.$productos->total().' producto(s) de tu empresa.',
        'textoConfirmar' => 'Descargar',
    ])

    @include('partials.modal-confirmacion', [
        'id' => 'modal_exportar_pdf_productos',
        'titulo' => 'Descargar PDF',
        'mensaje' => 'Se va a abrir un PDF listo para imprimir con los productos que coinciden con el filtro actual (búsqueda, marca, categoría y/o proveedor).',
        'textoConfirmar' => 'Descargar',
    ])

    {{-- Modal: reponer stock --}}
    <div id="modal_reponer_stock" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h2 class="text-lg font-semibold">Reponer stock</h2>
                <button type="button" id="btn_cerrar_reponer_stock" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
            </div>

            <div class="px-6 py-4 overflow-y-auto flex-1 space-y-4">
                <div class="relative">
                    <label for="buscador_reponer_stock" class="block text-sm font-medium mb-1">Buscar producto</label>
                    <input type="text" id="buscador_reponer_stock" autocomplete="off" placeholder="Escribí para buscar (nombre o código)..."
                        class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <ul id="resultados_reponer_stock" class="hidden absolute z-10 mt-1 w-full max-h-56 overflow-auto rounded border border-slate-200 bg-white shadow-lg"></ul>
                </div>

                <div id="lista_reponer_stock" class="divide-y border border-slate-200 rounded">
                    <p id="lista_reponer_stock_vacia" class="p-4 text-sm text-slate-500 text-center">
                        Buscá productos arriba para agregarlos a la lista.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="reponer_stock_proveedor" class="block text-sm font-medium mb-1">Proveedor (opcional)</label>
                        <select id="reponer_stock_proveedor" class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="">Sin especificar</option>
                            @foreach ($proveedoresDisponibles as $proveedorReponer)
                                <option value="{{ $proveedorReponer->id }}">{{ $proveedorReponer->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="reponer_stock_nota" class="block text-sm font-medium mb-1">Nota (opcional)</label>
                        <input type="text" id="reponer_stock_nota" maxlength="1000" placeholder="Ej: Factura #123"
                            class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('productos.reposiciones.store') }}" id="form_reponer_stock" class="px-6 py-4 border-t flex items-center justify-end gap-3">
                @csrf
                <div id="reponer_stock_inputs_ocultos"></div>
                <button type="button" id="btn_cancelar_reponer_stock" class="text-sm text-slate-500 hover:underline">Cancelar</button>
                <button type="submit" id="btn_guardar_reponer_stock" disabled
                    class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    Guardar reposición
                </button>
            </form>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <form method="GET" action="{{ route('productos.index') }}" class="flex flex-wrap items-end gap-2">
            <div>
                <label for="buscar" class="block text-xs text-slate-500 mb-1">Buscar</label>
                <input type="text" id="buscar" name="buscar" value="{{ $buscar }}" placeholder="Nombre, categoría o marca..."
                    class="w-56 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            </div>
            <div>
                <label for="marca" class="block text-xs text-slate-500 mb-1">Marca</label>
                <select id="marca" name="marca" class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Todas</option>
                    @foreach ($marcasDisponibles as $opcionMarca)
                        <option value="{{ $opcionMarca }}" @selected($marca === $opcionMarca)>{{ $opcionMarca }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="categoria" class="block text-xs text-slate-500 mb-1">Categoría</label>
                <select id="categoria" name="categoria" class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Todas</option>
                    @foreach ($categoriasDisponibles as $opcionCategoria)
                        <option value="{{ $opcionCategoria }}" @selected($categoria === $opcionCategoria)>{{ $opcionCategoria }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="proveedor_id" class="block text-xs text-slate-500 mb-1">Proveedor</label>
                <select id="proveedor_id" name="proveedor_id" class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Todos</option>
                    @foreach ($proveedoresDisponibles as $opcionProveedor)
                        <option value="{{ $opcionProveedor->id }}" @selected($proveedorId === $opcionProveedor->id)>{{ $opcionProveedor->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <input type="hidden" name="orden" value="{{ $orden }}">
            <input type="hidden" name="dir" value="{{ $direccion }}">
            <button type="submit" class="rounded px-3 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                Buscar
            </button>
            @if ($buscar !== '' || $marca !== '' || $categoria !== '' || $proveedorId)
                <a href="{{ route('productos.index', ['orden' => $orden, 'dir' => $direccion, 'por_pagina' => $porPagina]) }}" class="text-sm text-slate-500 hover:underline pb-2">
                    Limpiar
                </a>
            @endif
        </form>

        <form method="GET" action="{{ route('productos.index') }}" class="flex items-center gap-2 text-sm">
            <input type="hidden" name="buscar" value="{{ $buscar }}">
            <input type="hidden" name="marca" value="{{ $marca }}">
            <input type="hidden" name="categoria" value="{{ $categoria }}">
            <input type="hidden" name="proveedor_id" value="{{ $proveedorId }}">
            <input type="hidden" name="orden" value="{{ $orden }}">
            <input type="hidden" name="dir" value="{{ $direccion }}">
            <label for="por_pagina" class="text-slate-500">Mostrar</label>
            <select id="por_pagina" name="por_pagina" onchange="this.form.submit()"
                class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                <option value="10" @selected($porPagina === 10)>10</option>
                <option value="50" @selected($porPagina === 50)>50</option>
                <option value="100" @selected($porPagina === 100)>100</option>
            </select>
        </form>
    </div>

    <div id="bloque_seleccion" class="hidden mb-4 bg-white rounded-lg shadow p-4 flex flex-wrap items-center gap-4">
        <p class="text-sm w-full">
            <strong id="contador_seleccion">0</strong> producto(s) seleccionados
            <a href="#" id="vaciar_seleccion" class="ml-2 text-slate-500 hover:underline">Vaciar selección</a>
        </p>

        <form method="POST" action="{{ route('productos.grupos.store') }}" id="form_crear_grupo" class="flex items-center gap-2">
            @csrf
            <input type="text" name="nombre" required placeholder="Nombre del grupo"
                class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            <div id="ids_seleccionados"></div>
            <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                Crear grupo con estos productos
            </button>
        </form>

        @if ($grupos->isNotEmpty())
            <form method="POST" id="form_agregar_grupo" data-action-template="{{ route('productos.grupos.agregarProductos', ['grupo' => '__ID__']) }}" class="flex items-center gap-2">
                @csrf
                <select id="select_grupo_existente" class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Elegir grupo existente…</option>
                    @foreach ($grupos as $grupoExistente)
                        <option value="{{ $grupoExistente->id }}">{{ $grupoExistente->nombre }}</option>
                    @endforeach
                </select>
                <div id="ids_seleccionados_agregar"></div>
                <button type="submit" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                    Agregar a ese grupo
                </button>
            </form>
        @endif

        <form method="POST" action="{{ route('productos.promociones.previsualizar') }}" id="form_crear_promo" class="flex items-center gap-2">
            @csrf
            <div id="ids_seleccionados_promo"></div>
            <button type="submit" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                Crear promo
            </button>
        </form>

        <form method="POST" action="{{ route('productos.exportarPdf') }}" id="form_pdf_seleccionados" target="_blank" class="flex items-center gap-2">
            @csrf
            <div id="ids_seleccionados_pdf"></div>
            <button type="submit" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                Descargar PDF de estos seleccionados
            </button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium"></th>
                    <th class="px-4 py-2 font-medium">Nombre</th>
                    <th class="px-4 py-2 font-medium">Código</th>
                    <th class="px-4 py-2 font-medium">
                        <a href="{{ request()->fullUrlWithQuery(['orden' => 'categoria', 'dir' => ($orden === 'categoria' && $direccion === 'asc') ? 'desc' : 'asc', 'page' => null]) }}" class="hover:underline">
                            Categoría{{ $orden === 'categoria' ? ($direccion === 'asc' ? ' ↑' : ' ↓') : '' }}
                        </a>
                    </th>
                    <th class="px-4 py-2 font-medium">
                        <a href="{{ request()->fullUrlWithQuery(['orden' => 'marca', 'dir' => ($orden === 'marca' && $direccion === 'asc') ? 'desc' : 'asc', 'page' => null]) }}" class="hover:underline">
                            Marca{{ $orden === 'marca' ? ($direccion === 'asc' ? ' ↑' : ' ↓') : '' }}
                        </a>
                    </th>
                    <th class="px-4 py-2 font-medium text-right">Precio</th>
                    @if (auth()->user()->empresa->controla_stock)
                        <th class="px-4 py-2 font-medium text-right">Stock</th>
                    @endif
                    <th class="px-4 py-2 font-medium">Estado</th>
                    <th class="px-4 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($productos as $producto)
                    <tr>
                        <td class="px-4 py-2">
                            @unless ($producto->es_promocion)
                                <input type="checkbox" class="seleccion-producto rounded border border-slate-300" data-id="{{ $producto->id }}">
                            @endunless
                        </td>
                        <td class="px-4 py-2">
                            {{ $producto->nombre }}
                            @if ($producto->es_promocion)
                                <span class="ml-1 text-xs font-medium rounded px-1.5 py-0.5 bg-amber-100 text-amber-800">Promo</span>
                            @elseif (! empty($producto->tramosDePrecio()))
                                <span class="ml-1 text-xs font-medium rounded px-1.5 py-0.5 bg-sky-100 text-sky-800">Precio por cantidad</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-slate-500">{{ $producto->codigo ?? '—' }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $producto->categoria ?? '—' }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $producto->marca ?? '—' }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($producto->precio, 2, ',', '.') }}</td>
                        @if (auth()->user()->empresa->controla_stock)
                            <td class="px-4 py-2 text-right">{{ $producto->stock }}</td>
                        @endif
                        <td class="px-4 py-2">
                            <span class="text-xs font-medium rounded px-2 py-1 @class(['bg-emerald-100 text-emerald-800' => $producto->activo, 'bg-slate-200 text-slate-700' => ! $producto->activo])">
                                {{ $producto->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ $producto->es_promocion ? route('productos.promociones.edit', $producto) : route('productos.edit', $producto) }}" class="text-sm text-slate-600 hover:underline">Editar</a>
                                @if ($producto->activo)
                                    <form method="POST" action="{{ route('productos.desactivar', $producto) }}">
                                        @csrf
                                        <button type="submit" class="text-sm text-red-600 hover:underline">Desactivar</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('productos.activar', $producto) }}">
                                        @csrf
                                        <button type="submit" class="text-sm text-emerald-600 hover:underline">Activar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->empresa->controla_stock ? 9 : 8 }}" class="px-4 py-6 text-center text-slate-500">
                            @if ($buscar !== '')
                                No encontramos productos que coincidan con "{{ $buscar }}".
                            @else
                                Todavía no cargaste productos. Empezá con "+ Nuevo producto" o importando un Excel.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $productos->links() }}
    </div>

    <script>
        (function () {
            const storageKey = 'svm_seleccion_productos_{{ auth()->user()->empresa_id }}';

            function leerSeleccion() {
                try {
                    return new Set(JSON.parse(localStorage.getItem(storageKey) || '[]'));
                } catch (e) {
                    return new Set();
                }
            }

            function guardarSeleccion(set) {
                try {
                    localStorage.setItem(storageKey, JSON.stringify(Array.from(set)));
                } catch (e) {}
            }

            let seleccion = leerSeleccion();

            const contador = document.getElementById('contador_seleccion');
            const bloque = document.getElementById('bloque_seleccion');

            function actualizarBloque() {
                contador.textContent = seleccion.size;
                bloque.classList.toggle('hidden', seleccion.size === 0);
            }

            document.querySelectorAll('.seleccion-producto').forEach(function (checkbox) {
                const id = checkbox.dataset.id;
                checkbox.checked = seleccion.has(id);

                checkbox.addEventListener('change', function () {
                    if (checkbox.checked) {
                        seleccion.add(id);
                    } else {
                        seleccion.delete(id);
                    }
                    guardarSeleccion(seleccion);
                    actualizarBloque();
                });
            });

            document.getElementById('vaciar_seleccion').addEventListener('click', function (evento) {
                evento.preventDefault();
                seleccion = new Set();
                guardarSeleccion(seleccion);
                document.querySelectorAll('.seleccion-producto').forEach(function (cb) {
                    cb.checked = false;
                });
                actualizarBloque();
            });

            const formCrearGrupo = document.getElementById('form_crear_grupo');
            formCrearGrupo.addEventListener('submit', function () {
                const contenedor = document.getElementById('ids_seleccionados');
                contenedor.innerHTML = '';

                seleccion.forEach(function (id) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'productos_ids[]';
                    input.value = id;
                    contenedor.appendChild(input);
                });

                localStorage.removeItem(storageKey);
            });

            const formAgregarGrupo = document.getElementById('form_agregar_grupo');
            if (formAgregarGrupo) {
                formAgregarGrupo.addEventListener('submit', function (evento) {
                    evento.preventDefault();

                    const grupoId = document.getElementById('select_grupo_existente').value;

                    if (!grupoId) {
                        alert('Elegí un grupo antes de agregar los productos seleccionados.');
                        return;
                    }

                    const contenedor = document.getElementById('ids_seleccionados_agregar');
                    contenedor.innerHTML = '';

                    seleccion.forEach(function (id) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'productos_ids[]';
                        input.value = id;
                        contenedor.appendChild(input);
                    });

                    localStorage.removeItem(storageKey);

                    formAgregarGrupo.action = formAgregarGrupo.dataset.actionTemplate.replace('__ID__', grupoId);
                    formAgregarGrupo.submit();
                });
            }

            document.getElementById('form_crear_promo').addEventListener('submit', function (evento) {
                if (seleccion.size < 2) {
                    evento.preventDefault();
                    alert('Seleccioná al menos 2 productos para armar una promo.');
                    return;
                }

                const contenedor = document.getElementById('ids_seleccionados_promo');
                contenedor.innerHTML = '';

                seleccion.forEach(function (id) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'productos_ids[]';
                    input.value = id;
                    contenedor.appendChild(input);
                });

                localStorage.removeItem(storageKey);
            });

            // A diferencia de "crear grupo"/"crear promo" (que consumen la
            // selección), descargar el PDF no cambia nada — se deja la
            // selección tal cual para poder, por ejemplo, exportarla después
            // a Excel también sin tener que tildar todo de nuevo.
            document.getElementById('form_pdf_seleccionados').addEventListener('submit', function () {
                const contenedor = document.getElementById('ids_seleccionados_pdf');
                contenedor.innerHTML = '';

                seleccion.forEach(function (id) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'productos_ids[]';
                    input.value = id;
                    contenedor.appendChild(input);
                });
            });

            actualizarBloque();
        })();

        (function () {
            const modal = document.getElementById('modal_exportar_productos');
            const cancelar = document.getElementById('modal_exportar_productos_cancelar');
            const confirmar = document.getElementById('modal_exportar_productos_confirmar');

            document.getElementById('btn_exportar_productos').addEventListener('click', function () {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });

            cancelar.addEventListener('click', function () {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });

            confirmar.addEventListener('click', function () {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                window.location.href = '{{ route('productos.exportar', request()->only('buscar')) }}';
            });
        })();

        (function () {
            const modal = document.getElementById('modal_exportar_pdf_productos');
            const cancelar = document.getElementById('modal_exportar_pdf_productos_cancelar');
            const confirmar = document.getElementById('modal_exportar_pdf_productos_confirmar');

            document.getElementById('btn_exportar_pdf_productos').addEventListener('click', function () {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });

            cancelar.addEventListener('click', function () {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });

            confirmar.addEventListener('click', function () {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                window.open('{{ route('productos.exportarPdf', request()->only(['buscar', 'marca', 'categoria', 'proveedor_id'])) }}', '_blank');
            });
        })();

        (function () {
            const productos = @json($productosParaReponer);

            const boton = document.getElementById('btn_abrir_reponer_stock');
            const modal = document.getElementById('modal_reponer_stock');
            const btnCerrar = document.getElementById('btn_cerrar_reponer_stock');
            const btnCancelar = document.getElementById('btn_cancelar_reponer_stock');
            const input = document.getElementById('buscador_reponer_stock');
            const lista = document.getElementById('resultados_reponer_stock');
            const listaAgregados = document.getElementById('lista_reponer_stock');
            const filaVacia = document.getElementById('lista_reponer_stock_vacia');
            const btnGuardar = document.getElementById('btn_guardar_reponer_stock');
            const form = document.getElementById('form_reponer_stock');
            const inputsOcultos = document.getElementById('reponer_stock_inputs_ocultos');

            let resultados = [];
            let indiceActivo = -1;
            const agregados = new Map(); // producto_id -> { inputCantidad }

            function abrirModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                input.focus();
            }

            function cerrarModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            boton.addEventListener('click', abrirModal);
            btnCerrar.addEventListener('click', cerrarModal);
            btnCancelar.addEventListener('click', cerrarModal);

            function renderizarResultados() {
                lista.innerHTML = '';

                if (resultados.length === 0) {
                    lista.classList.add('hidden');
                    return;
                }

                resultados.forEach(function (producto, indice) {
                    const li = document.createElement('li');
                    li.textContent = producto.nombre + (producto.codigo ? ' (' + producto.codigo + ')' : '') + ' — stock actual: ' + producto.stock;
                    li.className = 'px-3 py-2 text-sm cursor-pointer' + (indice === indiceActivo ? ' bg-slate-100' : '');
                    li.addEventListener('mousedown', function (evento) {
                        evento.preventDefault();
                        agregarProducto(producto);
                    });
                    lista.appendChild(li);
                });

                lista.classList.remove('hidden');
            }

            function actualizarBotonGuardar() {
                let hayAlgunaCantidad = false;

                agregados.forEach(function (entrada) {
                    if (parseInt(entrada.inputCantidad.value, 10) > 0) hayAlgunaCantidad = true;
                });

                btnGuardar.disabled = !hayAlgunaCantidad;
            }

            function agregarProducto(producto) {
                input.value = '';
                resultados = [];
                indiceActivo = -1;
                renderizarResultados();

                // Ya estaba en la lista: en vez de duplicar la fila, llevar
                // el foco a su cantidad para que la ajusten ahí.
                if (agregados.has(producto.id)) {
                    const existente = agregados.get(producto.id).inputCantidad;
                    existente.focus();
                    existente.select();
                    return;
                }

                filaVacia.classList.add('hidden');

                const fila = document.createElement('div');
                fila.className = 'flex items-center gap-3 px-3 py-2';

                const info = document.createElement('div');
                info.className = 'flex-1 text-sm';
                info.innerHTML = '<p class="font-medium">' + producto.nombre + '</p>'
                    + '<p class="text-slate-500 text-xs">Stock actual: ' + producto.stock + '</p>';

                const inputCantidad = document.createElement('input');
                inputCantidad.type = 'number';
                inputCantidad.min = '1';
                inputCantidad.placeholder = 'Cantidad que llegó';
                inputCantidad.className = 'w-40 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500';
                inputCantidad.addEventListener('input', actualizarBotonGuardar);

                const btnQuitar = document.createElement('button');
                btnQuitar.type = 'button';
                btnQuitar.textContent = '✕';
                btnQuitar.setAttribute('aria-label', 'Quitar');
                btnQuitar.className = 'text-slate-400 hover:text-red-600 px-2';
                btnQuitar.addEventListener('click', function () {
                    fila.remove();
                    agregados.delete(producto.id);
                    if (agregados.size === 0) filaVacia.classList.remove('hidden');
                    actualizarBotonGuardar();
                });

                fila.appendChild(info);
                fila.appendChild(inputCantidad);
                fila.appendChild(btnQuitar);
                listaAgregados.appendChild(fila);

                agregados.set(producto.id, { inputCantidad: inputCantidad });
                actualizarBotonGuardar();
                inputCantidad.focus();
            }

            input.addEventListener('input', function () {
                const texto = input.value.trim().toLowerCase();
                indiceActivo = -1;

                resultados = texto === '' ? [] : productos.filter(function (producto) {
                    return producto.nombre.toLowerCase().includes(texto)
                        || (producto.codigo && producto.codigo.toLowerCase().includes(texto));
                }).slice(0, 8);

                renderizarResultados();
            });

            input.addEventListener('keydown', function (evento) {
                if (evento.key === 'Enter') {
                    evento.preventDefault();

                    const elegido = indiceActivo >= 0 ? resultados[indiceActivo] : (resultados.length === 1 ? resultados[0] : null);

                    if (elegido) agregarProducto(elegido);

                    return;
                }

                if (resultados.length === 0) return;

                if (evento.key === 'ArrowDown') {
                    evento.preventDefault();
                    indiceActivo = (indiceActivo + 1) % resultados.length;
                    renderizarResultados();
                } else if (evento.key === 'ArrowUp') {
                    evento.preventDefault();
                    indiceActivo = (indiceActivo - 1 + resultados.length) % resultados.length;
                    renderizarResultados();
                } else if (evento.key === 'Escape') {
                    resultados = [];
                    renderizarResultados();
                }
            });

            input.addEventListener('blur', function () {
                setTimeout(function () {
                    resultados = [];
                    renderizarResultados();
                }, 100);
            });

            form.addEventListener('submit', function (evento) {
                inputsOcultos.innerHTML = '';

                const proveedorId = document.getElementById('reponer_stock_proveedor').value;
                if (proveedorId) {
                    const inputProveedor = document.createElement('input');
                    inputProveedor.type = 'hidden';
                    inputProveedor.name = 'proveedor_id';
                    inputProveedor.value = proveedorId;
                    inputsOcultos.appendChild(inputProveedor);
                }

                const nota = document.getElementById('reponer_stock_nota').value.trim();
                if (nota) {
                    const inputNota = document.createElement('input');
                    inputNota.type = 'hidden';
                    inputNota.name = 'nota';
                    inputNota.value = nota;
                    inputsOcultos.appendChild(inputNota);
                }

                let indice = 0;
                agregados.forEach(function (entrada, productoId) {
                    const cantidad = parseInt(entrada.inputCantidad.value, 10);

                    if (!(cantidad > 0)) return;

                    const inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'items[' + indice + '][producto_id]';
                    inputId.value = productoId;
                    inputsOcultos.appendChild(inputId);

                    const inputCant = document.createElement('input');
                    inputCant.type = 'hidden';
                    inputCant.name = 'items[' + indice + '][cantidad]';
                    inputCant.value = cantidad;
                    inputsOcultos.appendChild(inputCant);

                    indice++;
                });

                if (indice === 0) {
                    evento.preventDefault();
                    alert('Cargá alguna cantidad antes de guardar.');
                }
            });
        })();
    </script>
@endsection
