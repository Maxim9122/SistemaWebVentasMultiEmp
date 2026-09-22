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
    </script>
@endsection
