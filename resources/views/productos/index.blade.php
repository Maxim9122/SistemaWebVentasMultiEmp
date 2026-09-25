@extends('layouts.app')

@section('titulo', 'Productos')

@section('contenido')
    <div class="mb-4 flex flex-wrap justify-end gap-2">
        <a href="{{ route('productos.proveedores.index') }}" class="inline-flex items-center gap-1.5 rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                <rect x="1" y="7" width="13" height="9" rx="1"/>
                <path d="M14 10h4l3 3v3h-3"/>
                <circle cx="6" cy="18" r="1.5"/>
                <circle cx="16.5" cy="18" r="1.5"/>
            </svg>
            Proveedores
        </a>
        <a href="{{ route('productos.importaciones.index') }}" class="inline-flex items-center gap-1.5 rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                <path d="M12 3v10m0 0-3.5-3.5M12 13l3.5-3.5"/>
                <path d="M4 15v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>
            </svg>
            Importaciones
        </a>
        <a href="{{ route('productos.ajustesPrecio.index') }}" class="inline-flex items-center gap-1.5 rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                <circle cx="12" cy="12" r="9"/>
                <path d="M12 7v5l3 3"/>
            </svg>
            Historial de ajustes de precio
        </a>
        <a href="{{ route('productos.reposiciones.index') }}" class="inline-flex items-center gap-1.5 rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                <circle cx="12" cy="12" r="9"/>
                <path d="M12 7v5l3 3"/>
            </svg>
            Reposiciones
        </a>
        <button type="button" id="btn_abrir_reponer_stock" class="inline-flex items-center gap-1.5 rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                <rect x="3" y="8" width="18" height="12" rx="1"/>
                <path d="M3 8l2-4h14l2 4"/>
                <path d="M12 12v4M10 14h4"/>
            </svg>
            Reponer stock
        </button>
        <a href="{{ route('productos.grupos.index') }}" class="inline-flex items-center gap-1.5 rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                <path d="M4 4h7l9 9-7 7-9-9V4z"/>
                <circle cx="8.5" cy="8.5" r="1" fill="currentColor" stroke="none"/>
            </svg>
            Grupos
        </a>
        <a href="{{ route('productos.importar.subir') }}" class="inline-flex items-center gap-1.5 rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                <rect x="3" y="4" width="18" height="16" rx="1"/>
                <path d="M3 9h18M3 14h18M9 4v16M15 4v16"/>
            </svg>
            Importar desde Excel
        </a>
        <button type="button" id="btn_exportar_productos" class="inline-flex items-center gap-1.5 rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                <path d="M12 4v10m0 0-3.5-3.5M12 14l3.5-3.5"/>
                <path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>
            </svg>
            Descargar Excel
        </button>
        <button type="button" id="btn_exportar_pdf_productos" class="inline-flex items-center gap-1.5 rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                <path d="M12 4v10m0 0-3.5-3.5M12 14l3.5-3.5"/>
                <path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>
            </svg>
            Descargar PDF
        </button>
        <a href="{{ route('productos.create') }}" class="inline-flex items-center gap-1.5 rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Nuevo producto
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

    {{-- Modal: editar producto --}}
    <div id="modal_editar_producto" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h2 class="text-lg font-semibold">Editar producto</h2>
                <button type="button" id="btn_cerrar_editar_producto" class="text-slate-400 hover:text-slate-600 p-1" aria-label="Cerrar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                        <path d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            <form method="POST" id="form_editar_producto" class="px-6 py-4 overflow-y-auto flex-1 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="editar_producto_nombre" class="block text-sm font-medium mb-1">Nombre</label>
                    <input id="editar_producto_nombre" name="nombre" type="text" required
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="editar_producto_codigo" class="block text-sm font-medium mb-1">Código / SKU</label>
                        <input id="editar_producto_codigo" name="codigo" type="text"
                            class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <div>
                        <label for="editar_producto_categoria" class="block text-sm font-medium mb-1">Categoría</label>
                        <input id="editar_producto_categoria" name="categoria" type="text"
                            class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                </div>

                <div>
                    <label for="editar_producto_marca" class="block text-sm font-medium mb-1">Marca</label>
                    <input id="editar_producto_marca" name="marca" type="text"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="editar_producto_precio" class="block text-sm font-medium mb-1">Precio</label>
                        <input id="editar_producto_precio" name="precio" type="number" step="0.01" min="0" required
                            class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <div>
                        <label for="editar_producto_costo" class="block text-sm font-medium mb-1">Costo</label>
                        <input id="editar_producto_costo" name="costo" type="number" step="0.01" min="0"
                            class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    @if (auth()->user()->empresa->controla_stock)
                        <div>
                            <label for="editar_producto_stock" class="block text-sm font-medium mb-1">Stock</label>
                            <input id="editar_producto_stock" name="stock" type="number" step="1" min="0"
                                class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                        </div>
                    @else
                        <input type="hidden" name="stock" value="0">
                    @endif
                    <div class="@if (! auth()->user()->empresa->controla_stock) col-span-2 @endif">
                        <label for="editar_producto_unidad" class="block text-sm font-medium mb-1">Unidad</label>
                        <input id="editar_producto_unidad" name="unidad" type="text" placeholder="kg, unidad, caja..."
                            class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                </div>

                <div>
                    <label for="editar_producto_proveedor_id" class="block text-sm font-medium mb-1">Proveedor</label>
                    <select id="editar_producto_proveedor_id" name="proveedor_id"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                        <option value="">— Sin proveedor —</option>
                        @foreach ($proveedoresDisponibles as $proveedorEditar)
                            <option value="{{ $proveedorEditar->id }}">{{ $proveedorEditar->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="border-t pt-4">
                    <p class="font-medium text-slate-900 mb-1">Precios por cantidad (opcional)</p>
                    <p class="text-slate-500 text-sm mb-3">
                        Hasta 3 tramos de descuento por volumen. Al vender, el carrito aplica solo por la cantidad
                        cargada — no hace falta elegir el precio a mano.
                    </p>
                    <div class="space-y-2">
                        @for ($n = 1; $n <= 3; $n++)
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="editar_producto_cantidad_minima_{{ $n }}" class="block text-xs text-slate-500 mb-1">A partir de (unidades)</label>
                                    <input id="editar_producto_cantidad_minima_{{ $n }}" name="cantidad_minima_{{ $n }}" type="number" step="1" min="2"
                                        class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                                </div>
                                <div>
                                    <label for="editar_producto_precio_cantidad_{{ $n }}" class="block text-xs text-slate-500 mb-1">Precio por unidad</label>
                                    <input id="editar_producto_precio_cantidad_{{ $n }}" name="precio_cantidad_{{ $n }}" type="number" step="0.01" min="0"
                                        class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </form>

            <div class="px-6 py-4 border-t flex justify-end gap-2">
                <button type="button" id="btn_cancelar_editar_producto" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </button>
                <button type="submit" form="form_editar_producto" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Guardar cambios
                </button>
            </div>
        </div>
    </div>

    {{-- Modal: reponer stock --}}
    <div id="modal_reponer_stock" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h2 class="text-lg font-semibold" id="titulo_reponer_stock">Reponer stock</h2>
                <button type="button" id="btn_cerrar_reponer_stock" class="text-slate-400 hover:text-slate-600 p-1" aria-label="Cerrar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                        <path d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
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

            <form method="POST" action="{{ route('productos.reposiciones.store') }}" id="form_reponer_stock"
                data-url-store="{{ route('productos.reposiciones.store') }}"
                data-url-update-template="{{ route('productos.reposiciones.update', ['reposicion' => '__ID__']) }}"
                class="px-6 py-4 border-t flex items-center justify-end gap-3">
                @csrf
                <div id="reponer_stock_inputs_ocultos"></div>
                <button type="button" id="btn_cancelar_reponer_stock" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:underline">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                        <path d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                    Cancelar
                </button>
                <button type="submit" id="btn_guardar_reponer_stock" disabled
                    class="inline-flex items-center gap-1.5 rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                        <path d="M5 13l4 4L19 7"/>
                    </svg>
                    <span id="texto_btn_guardar_reponer_stock">Guardar reposición</span>
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
            <button type="submit" class="inline-flex items-center gap-1.5 rounded px-3 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m20 20-3.5-3.5"/>
                </svg>
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

    <div class="mb-4 bg-white rounded-lg shadow p-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="porcentaje_ajuste_busqueda" class="block text-xs text-slate-500 mb-1">Ajustar precio (%)</label>
            <input type="number" id="porcentaje_ajuste_busqueda" step="0.01" placeholder="Ej: 10 o -5"
                class="w-40 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
        </div>
        <p class="text-xs text-slate-500 w-full">
            Cargá acá el porcentaje y despues tildá a qué productos aplicarlo: con el check del encabezado de la tabla (selecciona
            <strong>todo</strong> el resultado de la búsqueda actual, en todas las páginas) o tildando puntualmente los que quieras.
            El botón para aplicarlo aparece abajo apenas tildes algo. Las promos quedan siempre afuera.
        </p>
    </div>

    <div id="modal_ajustar_precio_busqueda" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h2 class="text-lg font-semibold mb-3">Ajustar precio</h2>
            <p id="mensaje_ajustar_precio_busqueda" class="text-sm text-slate-600 mb-4"></p>
            <div class="flex gap-2 justify-end">
                <button type="button" id="modal_ajustar_precio_busqueda_cancelar" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </button>
                <form method="POST" action="{{ route('productos.ajustarPrecioBusqueda') }}" id="form_ajustar_precio_busqueda">
                    @csrf
                    <input type="hidden" name="buscar" value="{{ $buscar }}">
                    <input type="hidden" name="marca" value="{{ $marca }}">
                    <input type="hidden" name="categoria" value="{{ $categoria }}">
                    <input type="hidden" name="proveedor_id" value="{{ $proveedorId }}">
                    <input type="hidden" name="orden" value="{{ $orden }}">
                    <input type="hidden" name="dir" value="{{ $direccion }}">
                    <input type="hidden" name="por_pagina" value="{{ $porPagina }}">
                    <input type="hidden" name="porcentaje" id="input_porcentaje_ajuste_busqueda">
                    <div id="ids_seleccionados_ajuste_precio"></div>
                    <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                        Confirmar ajuste
                    </button>
                </form>
            </div>
        </div>
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
            <button type="submit" class="inline-flex items-center gap-1.5 rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                    <path d="M12 4v10m0 0-3.5-3.5M12 14l3.5-3.5"/>
                    <path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>
                </svg>
                Descargar PDF de estos seleccionados
            </button>
        </form>

        <div class="flex items-center gap-2">
            <button type="button" id="btn_ajustar_precio_seleccionados" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                Aplicar % (de arriba) a estos seleccionados
            </button>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">
                        <input type="checkbox" id="check_todos_pagina" class="rounded border border-slate-300" title="Tildar/destildar TODO el resultado de la búsqueda (todas las páginas)">
                    </th>
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
                                @if ($producto->es_promocion)
                                    <a href="{{ route('productos.promociones.edit', $producto) }}" class="text-sm text-slate-600 hover:underline">Editar</a>
                                @else
                                    <button type="button" class="btn-editar-producto text-sm text-slate-600 hover:underline"
                                        data-url="{{ route('productos.update', $producto) }}"
                                        data-nombre="{{ $producto->nombre }}"
                                        data-codigo="{{ $producto->codigo }}"
                                        data-categoria="{{ $producto->categoria }}"
                                        data-marca="{{ $producto->marca }}"
                                        data-precio="{{ $producto->precio }}"
                                        data-costo="{{ $producto->costo }}"
                                        data-stock="{{ $producto->stock }}"
                                        data-unidad="{{ $producto->unidad }}"
                                        data-proveedor-id="{{ $producto->proveedor_id }}"
                                        data-cantidad-minima1="{{ $producto->cantidad_minima_1 }}"
                                        data-precio-cantidad1="{{ $producto->precio_cantidad_1 }}"
                                        data-cantidad-minima2="{{ $producto->cantidad_minima_2 }}"
                                        data-precio-cantidad2="{{ $producto->precio_cantidad_2 }}"
                                        data-cantidad-minima3="{{ $producto->cantidad_minima_3 }}"
                                        data-precio-cantidad3="{{ $producto->precio_cantidad_3 }}">
                                        Editar
                                    </button>
                                @endif
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
            const modal = document.getElementById('modal_ajustar_precio_busqueda');

            document.getElementById('modal_ajustar_precio_busqueda_cancelar').addEventListener('click', function () {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        })();

        (function () {
            const modal = document.getElementById('modal_editar_producto');
            const form = document.getElementById('form_editar_producto');
            const campoStock = document.getElementById('editar_producto_stock');

            function valor(boton, clave) {
                return boton.dataset[clave] || '';
            }

            function cerrarModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            document.querySelectorAll('.btn-editar-producto').forEach(function (boton) {
                boton.addEventListener('click', function () {
                    form.action = boton.dataset.url;
                    document.getElementById('editar_producto_nombre').value = valor(boton, 'nombre');
                    document.getElementById('editar_producto_codigo').value = valor(boton, 'codigo');
                    document.getElementById('editar_producto_categoria').value = valor(boton, 'categoria');
                    document.getElementById('editar_producto_marca').value = valor(boton, 'marca');
                    document.getElementById('editar_producto_precio').value = valor(boton, 'precio');
                    document.getElementById('editar_producto_costo').value = valor(boton, 'costo');
                    if (campoStock) campoStock.value = valor(boton, 'stock');
                    document.getElementById('editar_producto_unidad').value = valor(boton, 'unidad');
                    document.getElementById('editar_producto_proveedor_id').value = boton.dataset.proveedorId || '';

                    [1, 2, 3].forEach(function (n) {
                        document.getElementById('editar_producto_cantidad_minima_' + n).value = valor(boton, 'cantidadMinima' + n);
                        document.getElementById('editar_producto_precio_cantidad_' + n).value = valor(boton, 'precioCantidad' + n);
                    });

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.getElementById('editar_producto_nombre').focus();
                });
            });

            document.getElementById('btn_cerrar_editar_producto').addEventListener('click', cerrarModal);
            document.getElementById('btn_cancelar_editar_producto').addEventListener('click', cerrarModal);
        })();

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

            // Todos los ids que matchean la búsqueda actual, en TODAS las
            // páginas (no solo los que están renderizados ahora) — así el
            // check general puede tildar de verdad "todo el resultado",
            // no solo lo que se ve en pantalla.
            const idsAjustablesBusqueda = @json($idsAjustablesBusqueda->map(fn ($id) => (string) $id));

            const checkTodosPagina = document.getElementById('check_todos_pagina');
            if (checkTodosPagina) {
                // Refleja el estado real al cargar: si ya está TODO el
                // resultado de la búsqueda tildado (por ej. se tildó "todo"
                // en una página anterior), que no quede destildado a la vista.
                checkTodosPagina.checked = idsAjustablesBusqueda.length > 0
                    && idsAjustablesBusqueda.every(function (id) { return seleccion.has(id); });

                checkTodosPagina.addEventListener('change', function () {
                    idsAjustablesBusqueda.forEach(function (id) {
                        if (checkTodosPagina.checked) {
                            seleccion.add(id);
                        } else {
                            seleccion.delete(id);
                        }
                    });

                    // Los checkboxes visibles de esta página reflejan el
                    // nuevo estado (los de otras páginas se van a ver
                    // tildados solos al navegar, porque leen de `seleccion`
                    // guardada en localStorage al cargar cada página).
                    document.querySelectorAll('.seleccion-producto').forEach(function (checkbox) {
                        checkbox.checked = seleccion.has(checkbox.dataset.id);
                    });

                    guardarSeleccion(seleccion);
                    actualizarBloque();
                });
            }

            document.getElementById('vaciar_seleccion').addEventListener('click', function (evento) {
                evento.preventDefault();
                seleccion = new Set();
                guardarSeleccion(seleccion);
                document.querySelectorAll('.seleccion-producto').forEach(function (cb) {
                    cb.checked = false;
                });
                if (checkTodosPagina) checkTodosPagina.checked = false;
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

            document.getElementById('btn_ajustar_precio_seleccionados').addEventListener('click', function () {
                if (seleccion.size === 0) {
                    alert('Tildá al menos un producto antes de aplicar el ajuste.');
                    return;
                }

                const inputPorcentaje = document.getElementById('porcentaje_ajuste_busqueda');
                const porcentaje = parseFloat(inputPorcentaje.value);

                if (isNaN(porcentaje) || porcentaje === 0) {
                    alert('Ingresá el porcentaje arriba (por ejemplo 10 para aumentar, o -5 para descontar) antes de aplicarlo a los seleccionados.');
                    inputPorcentaje.focus();
                    return;
                }

                const modal = document.getElementById('modal_ajustar_precio_busqueda');
                const mensaje = document.getElementById('mensaje_ajustar_precio_busqueda');
                const signo = porcentaje > 0 ? '+' : '';

                mensaje.textContent = 'Se va a aplicar ' + signo + porcentaje + '% al precio de los ' + seleccion.size
                    + ' producto(s) que tildaste a mano. Esta acción no se puede deshacer desde acá (sí desde el Historial de ajustes de precio).';

                document.getElementById('input_porcentaje_ajuste_busqueda').value = porcentaje;

                const contenedorIds = document.getElementById('ids_seleccionados_ajuste_precio');
                contenedorIds.innerHTML = '';
                seleccion.forEach(function (id) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'productos_ids[]';
                    input.value = id;
                    contenedorIds.appendChild(input);
                });

                modal.classList.remove('hidden');
                modal.classList.add('flex');
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
            const reposicionParaEditar = @json($reposicionParaEditar);

            const boton = document.getElementById('btn_abrir_reponer_stock');
            const modal = document.getElementById('modal_reponer_stock');
            const titulo = document.getElementById('titulo_reponer_stock');
            const btnCerrar = document.getElementById('btn_cerrar_reponer_stock');
            const btnCancelar = document.getElementById('btn_cancelar_reponer_stock');
            const input = document.getElementById('buscador_reponer_stock');
            const lista = document.getElementById('resultados_reponer_stock');
            const listaAgregados = document.getElementById('lista_reponer_stock');
            const filaVacia = document.getElementById('lista_reponer_stock_vacia');
            const btnGuardar = document.getElementById('btn_guardar_reponer_stock');
            const textoBtnGuardar = document.getElementById('texto_btn_guardar_reponer_stock');
            const form = document.getElementById('form_reponer_stock');
            const inputsOcultos = document.getElementById('reponer_stock_inputs_ocultos');
            const selectProveedor = document.getElementById('reponer_stock_proveedor');
            const inputNota = document.getElementById('reponer_stock_nota');

            let resultados = [];
            let indiceActivo = -1;
            let reposicionEditandoId = null; // null = creando una reposición nueva
            const agregados = new Map(); // producto_id -> { inputCantidad }

            function abrirModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                input.focus();
            }

            function cerrarModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                resetearModal();
            }

            function resetearModal() {
                reposicionEditandoId = null;
                agregados.clear();
                listaAgregados.querySelectorAll('div.flex').forEach(function (fila) { fila.remove(); });
                filaVacia.classList.remove('hidden');
                selectProveedor.value = '';
                inputNota.value = '';
                titulo.textContent = 'Reponer stock';
                textoBtnGuardar.textContent = 'Guardar reposición';
                actualizarBotonGuardar();
            }

            function entrarModoEdicion(datos) {
                resetearModal();
                reposicionEditandoId = datos.id;
                titulo.textContent = 'Editar reposición';
                textoBtnGuardar.textContent = 'Guardar cambios';
                selectProveedor.value = datos.proveedor_id || '';
                inputNota.value = datos.nota || '';

                datos.items.forEach(function (item) {
                    const productoConocido = productos.find(function (p) { return p.id === item.producto_id; });
                    agregarProducto({
                        id: item.producto_id,
                        nombre: item.nombre,
                        codigo: productoConocido ? productoConocido.codigo : null,
                        stock: productoConocido ? productoConocido.stock : null,
                    }, item.cantidad);
                });

                actualizarBotonGuardar();
                abrirModal();
            }

            boton.addEventListener('click', abrirModal);
            btnCerrar.addEventListener('click', cerrarModal);
            btnCancelar.addEventListener('click', cerrarModal);

            if (reposicionParaEditar) {
                entrarModoEdicion(reposicionParaEditar);

                // Sacar el ?editar_reposicion=… de la URL una vez abierto,
                // para que un F5 no reabra el modal de nuevo solo.
                const url = new URL(window.location.href);
                url.searchParams.delete('editar_reposicion');
                window.history.replaceState({}, '', url);
            }

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
                // Editando, se puede guardar igual con la lista en cero (es
                // sacar todas las líneas una por una = deshacer completo).
                // Creando, hace falta al menos una cantidad cargada.
                if (reposicionEditandoId !== null) {
                    btnGuardar.disabled = false;
                    return;
                }

                let hayAlgunaCantidad = false;

                agregados.forEach(function (entrada) {
                    if (parseInt(entrada.inputCantidad.value, 10) > 0) hayAlgunaCantidad = true;
                });

                btnGuardar.disabled = !hayAlgunaCantidad;
            }

            function agregarProducto(producto, cantidadInicial) {
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
                    + '<p class="text-slate-500 text-xs">Stock actual: ' + (producto.stock !== null && producto.stock !== undefined ? producto.stock : '—') + '</p>';

                const inputCantidad = document.createElement('input');
                inputCantidad.type = 'number';
                inputCantidad.min = '1';
                inputCantidad.placeholder = 'Cantidad que llegó';
                if (cantidadInicial) inputCantidad.value = cantidadInicial;
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

                const editando = reposicionEditandoId !== null;
                form.action = editando
                    ? form.dataset.urlUpdateTemplate.replace('__ID__', reposicionEditandoId)
                    : form.dataset.urlStore;

                if (editando) {
                    const inputMetodo = document.createElement('input');
                    inputMetodo.type = 'hidden';
                    inputMetodo.name = '_method';
                    inputMetodo.value = 'PUT';
                    inputsOcultos.appendChild(inputMetodo);
                }

                const proveedorId = selectProveedor.value;
                if (proveedorId) {
                    const inputProveedor = document.createElement('input');
                    inputProveedor.type = 'hidden';
                    inputProveedor.name = 'proveedor_id';
                    inputProveedor.value = proveedorId;
                    inputsOcultos.appendChild(inputProveedor);
                }

                const nota = inputNota.value.trim();
                if (nota) {
                    const inputNotaOculto = document.createElement('input');
                    inputNotaOculto.type = 'hidden';
                    inputNotaOculto.name = 'nota';
                    inputNotaOculto.value = nota;
                    inputsOcultos.appendChild(inputNotaOculto);
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

                // Al crear, hace falta al menos un producto. Al editar, dejar
                // la lista en cero es válido (equivale a "deshacer" línea por
                // línea hasta no dejar nada) — el backend lo interpreta como
                // la reposición revertida entera.
                if (indice === 0 && !editando) {
                    evento.preventDefault();
                    alert('Cargá alguna cantidad antes de guardar.');
                }
            });
        })();
    </script>
@endsection
