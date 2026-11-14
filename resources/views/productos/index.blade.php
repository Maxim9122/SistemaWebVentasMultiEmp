@extends('layouts.app')

@section('titulo', 'Productos')

@section('contenido')
    {{-- Solo en compu (mouse real): mostrar el desplegable con hover, sin
    tocar el clic que ya funciona para celular/tablet más abajo. #menu_historial
    sigue arrancando con la clase "hidden" de Tailwind (display:none); este
    !important la pisa nada más mientras el mouse está encima del contenedor. --}}
    <style>
        @media (hover: hover) and (pointer: fine) {
            /* Dos selectores a propósito: el primero cubre pasar el mouse
               por el botón; el segundo (menú en :hover directo) evita que
               se cierre apenas el mouse entra al menú en sí — sin esto,
               un menú posicionado "absolute" no cuenta como parte del área
               con :hover de su contenedor y se cerraba de golpe. También se
               sacó el margen entre botón y menú (quedan pegados) para que
               no quede un hueco muerto en el medio al mover el mouse. */
            #contenedor_menu_historial:hover #menu_historial,
            #menu_historial:hover,
            #contenedor_menu_importar:hover #menu_importar,
            #menu_importar:hover {
                display: block !important;
            }
        }
    </style>

    <div class="mb-4 flex flex-wrap justify-end gap-2">
        <div class="relative" id="contenedor_menu_historial">
            <button type="button" id="btn_menu_historial" class="inline-flex items-center gap-1.5 rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 7v5l3 3"/>
                </svg>
                Historial
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 shrink-0">
                    <path d="M6 9l6 6 6-6"/>
                </svg>
            </button>
            <div id="menu_historial" class="hidden absolute left-0 top-full z-50 w-56 rounded-lg border border-slate-200 bg-white shadow-lg py-1">
                <a href="{{ route('productos.reposiciones.index') }}" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Reposiciones de stock</a>
                <a href="{{ route('productos.importaciones.index') }}" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Importaciones de productos</a>
                <a href="{{ route('productos.ajustesPrecio.index') }}" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Ajustes de precio</a>
            </div>
        </div>
        <a href="{{ route('productos.proveedores.index') }}" class="inline-flex items-center gap-1.5 rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                <rect x="1" y="7" width="13" height="9" rx="1"/>
                <path d="M14 10h4l3 3v3h-3"/>
                <circle cx="6" cy="18" r="1.5"/>
                <circle cx="16.5" cy="18" r="1.5"/>
            </svg>
            Proveedores
        </a>
        <a href="{{ route('productos.grupos.index') }}" class="inline-flex items-center gap-1.5 rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                <path d="M4 4h7l9 9-7 7-9-9V4z"/>
                <circle cx="8.5" cy="8.5" r="1" fill="currentColor" stroke="none"/>
            </svg>
            Grupos
        </a>
        <button type="button" id="btn_abrir_reponer_stock" class="inline-flex items-center gap-1.5 rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                <rect x="3" y="8" width="18" height="12" rx="1"/>
                <path d="M3 8l2-4h14l2 4"/>
                <path d="M12 12v4M10 14h4"/>
            </svg>
            Reponer stock
        </button>
        @if ($claudeVisionConfigurado)
            <a href="{{ route('productos.reposiciones.importarFoto.subir') }}" class="inline-flex items-center gap-1.5 rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                    <circle cx="12" cy="13" r="4"/>
                </svg>
                Reponer stock desde una foto
            </a>
        @endif
        <div class="relative" id="contenedor_menu_importar">
            <button type="button" id="btn_menu_importar" class="inline-flex items-center gap-1.5 rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                    <rect x="3" y="4" width="18" height="16" rx="1"/>
                    <path d="M3 9h18M3 14h18M9 4v16M15 4v16"/>
                </svg>
                Importar productos
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 shrink-0">
                    <path d="M6 9l6 6 6-6"/>
                </svg>
            </button>
            <div id="menu_importar" class="hidden absolute left-0 top-full z-50 w-56 rounded-lg border border-slate-200 bg-white shadow-lg py-1">
                <a href="{{ route('productos.importar.subir') }}" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Desde un Excel</a>
                @if ($claudeVisionConfigurado)
                    <a href="{{ route('productos.importarFoto.subir') }}" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Desde una foto</a>
                    <a href="{{ route('productos.importarPdf.subir') }}" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Desde un PDF</a>
                @endif
            </div>
        </div>
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
            + Agregar nuevo/s
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

    {{-- Modal: agregar al carrito/presupuesto rápido — un producto (botón de
    una fila sin tildar) o varios de una (botón de una fila ya tildada junto
    con otras, ver btn-carga-rapida más abajo) --}}
    <div id="modal_carga_rapida" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b">
                <h2 class="text-lg font-semibold" id="carga_rapida_titulo">Agregar producto</h2>
                <button type="button" id="btn_cerrar_carga_rapida" class="text-slate-400 hover:text-slate-600 p-1" aria-label="Cerrar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                        <path d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            <div class="px-6 py-4 overflow-y-auto flex-1 space-y-4">
                <div id="carga_rapida_contenido" class="space-y-4">
                    <p id="carga_rapida_cargando" class="text-sm text-slate-500">Cargando...</p>

                    <div class="overflow-x-auto">
                        <table id="carga_rapida_tabla" class="hidden w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-slate-500 border-b">
                                    <th class="px-2 py-2 font-medium">Producto</th>
                                    <th class="px-2 py-2 font-medium w-24">Cantidad</th>
                                    @if ($puedeCambiarPrecio)
                                        <th class="px-2 py-2 font-medium w-28">Precio</th>
                                    @endif
                                    <th class="px-2 py-2"></th>
                                </tr>
                            </thead>
                            <tbody id="carga_rapida_filas" class="divide-y"></tbody>
                        </table>
                    </div>

                    <div>
                        <p class="block text-sm font-medium mb-1">¿A dónde va?</p>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                                <input type="radio" name="carga_rapida_tipo" value="venta" checked>
                                Venta (carrito)
                            </label>
                            <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                                <input type="radio" name="carga_rapida_tipo" value="presupuesto">
                                Presupuesto
                            </label>
                        </div>
                    </div>

                    <div>
                        <label for="carga_rapida_cliente" class="block text-sm font-medium mb-1">Cliente (opcional)</label>
                        <input type="text" id="carga_rapida_cliente" autocomplete="off" placeholder="Consumidor Final"
                            class="w-full rounded border border-slate-300 text-sm px-2 py-1.5 focus:border-slate-500 focus:ring-slate-500">
                    </div>

                    <p id="carga_rapida_error" class="hidden text-sm text-red-600"></p>
                </div>

                {{-- Se muestra en vez de lo de arriba cuando ya hay un
                carrito/presupuesto en curso — nunca se decide solo, se le
                pregunta al usuario si sigue cargando ahí o arranca uno
                nuevo. --}}
                <div id="carga_rapida_pregunta" class="hidden space-y-3">
                    <p id="carga_rapida_pregunta_texto" class="text-sm text-slate-700"></p>
                    <div class="flex gap-2">
                        <button type="button" id="btn_carga_rapida_usar_existente" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                            Agregar ahí
                        </button>
                        <button type="button" id="btn_carga_rapida_crear_nuevo" class="rounded border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:border-slate-400">
                            Crear nuevo
                        </button>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t flex justify-end gap-2">
                <button type="button" id="btn_cancelar_carga_rapida" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </button>
                <button type="button" id="btn_confirmar_carga_rapida" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Agregar
                </button>
            </div>
        </div>
    </div>

    {{-- Aviso flotante para confirmar la carga rápida sin recargar la página --}}
    <div id="aviso_carga_rapida" class="hidden fixed bottom-4 right-4 z-50 rounded bg-slate-900 text-white px-4 py-2 text-sm shadow-lg"></div>

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
        <button type="button" id="btn_ajustar_precio_seleccionados" disabled
            class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400 disabled:opacity-50 disabled:cursor-not-allowed">
            Aplicar a <span id="texto_cantidad_seleccionados">0</span> seleccionado(s)
        </button>
        <p class="text-xs text-slate-500 w-full">
            Tildá a qué productos aplicarlo: con el check del encabezado de la tabla (selecciona <strong>todo</strong> el resultado
            de la búsqueda actual, en todas las páginas) o tildando puntualmente los que quieras. Las promos quedan siempre afuera.
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

    </div>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium whitespace-nowrap">
                        <label class="inline-flex items-center gap-1.5 cursor-pointer font-normal">
                            <input type="checkbox" id="check_todos_pagina" class="rounded border border-slate-300" title="Selecciona todo el resultado de la búsqueda, en todas las páginas">
                            Todos
                        </label>
                    </th>
                    <th class="px-4 py-2 font-medium whitespace-nowrap">Nombre</th>
                    <th class="px-4 py-2 font-medium whitespace-nowrap">Código</th>
                    <th class="px-4 py-2 font-medium whitespace-nowrap">
                        <a href="{{ request()->fullUrlWithQuery(['orden' => 'categoria', 'dir' => ($orden === 'categoria' && $direccion === 'asc') ? 'desc' : 'asc', 'page' => null]) }}" class="hover:underline">
                            Categoría{{ $orden === 'categoria' ? ($direccion === 'asc' ? ' ↑' : ' ↓') : '' }}
                        </a>
                    </th>
                    <th class="px-4 py-2 font-medium whitespace-nowrap">
                        <a href="{{ request()->fullUrlWithQuery(['orden' => 'marca', 'dir' => ($orden === 'marca' && $direccion === 'asc') ? 'desc' : 'asc', 'page' => null]) }}" class="hover:underline">
                            Marca{{ $orden === 'marca' ? ($direccion === 'asc' ? ' ↑' : ' ↓') : '' }}
                        </a>
                    </th>
                    <th class="px-4 py-2 font-medium whitespace-nowrap">Proveedor</th>
                    <th class="px-4 py-2 font-medium text-right whitespace-nowrap">Precio</th>
                    <th class="px-4 py-2 font-medium text-right whitespace-nowrap">Stock</th>
                    <th class="px-4 py-2 font-medium whitespace-nowrap">Estado</th>
                    <th class="px-4 py-2 font-medium whitespace-nowrap"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($productos as $producto)
                    <tr class="even:bg-slate-50 has-[:checked]:bg-slate-200">
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
                        <td class="px-4 py-2 text-slate-500 whitespace-nowrap">{{ $producto->codigo ?? '—' }}</td>
                        <td class="px-4 py-2 text-slate-500 whitespace-nowrap">{{ $producto->categoria ?? '—' }}</td>
                        <td class="px-4 py-2 text-slate-500 whitespace-nowrap">{{ $producto->marca ?? '—' }}</td>
                        <td class="px-4 py-2 text-slate-500 whitespace-nowrap">{{ $producto->proveedor->nombre ?? '—' }}</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">${{ number_format($producto->precio, 2, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">{{ $producto->stock }}</td>
                        <td class="px-4 py-2">
                            <span class="text-xs font-medium rounded px-2 py-1 @class(['bg-emerald-100 text-emerald-800' => $producto->activo, 'bg-slate-200 text-slate-700' => ! $producto->activo])">
                                {{ $producto->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <div class="flex items-center justify-end gap-3">
                                @unless ($producto->es_promocion)
                                    <button type="button" class="btn-carga-rapida text-slate-500 hover:text-slate-900" title="Agregar al carrito o a un presupuesto"
                                        data-id="{{ $producto->id }}"
                                        data-nombre="{{ $producto->nombre }}"
                                        data-precio="{{ $producto->precio }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                                            <circle cx="9" cy="21" r="1"/>
                                            <circle cx="20" cy="21" r="1"/>
                                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                                        </svg>
                                    </button>
                                @endunless
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
                        <td colspan="10" class="px-4 py-6 text-center text-slate-500">
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
            const boton = document.getElementById('btn_menu_historial');
            const menu = document.getElementById('menu_historial');

            boton.addEventListener('click', function (evento) {
                evento.stopPropagation();
                menu.classList.toggle('hidden');
            });

            document.addEventListener('click', function (evento) {
                if (!menu.classList.contains('hidden') && !menu.contains(evento.target)) {
                    menu.classList.add('hidden');
                }
            });
        })();

        (function () {
            const boton = document.getElementById('btn_menu_importar');
            const menu = document.getElementById('menu_importar');

            boton.addEventListener('click', function (evento) {
                evento.stopPropagation();
                menu.classList.toggle('hidden');
            });

            document.addEventListener('click', function (evento) {
                if (!menu.classList.contains('hidden') && !menu.contains(evento.target)) {
                    menu.classList.add('hidden');
                }
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
            const modal = document.getElementById('modal_carga_rapida');
            const titulo = document.getElementById('carga_rapida_titulo');
            const cargando = document.getElementById('carga_rapida_cargando');
            const tabla = document.getElementById('carga_rapida_tabla');
            const filas = document.getElementById('carga_rapida_filas');
            const campoError = document.getElementById('carga_rapida_error');
            const btnConfirmar = document.getElementById('btn_confirmar_carga_rapida');
            const aviso = document.getElementById('aviso_carga_rapida');
            const contenido = document.getElementById('carga_rapida_contenido');
            const pregunta = document.getElementById('carga_rapida_pregunta');
            const preguntaTexto = document.getElementById('carga_rapida_pregunta_texto');
            const btnUsarExistente = document.getElementById('btn_carga_rapida_usar_existente');
            const btnCrearNuevo = document.getElementById('btn_carga_rapida_crear_nuevo');
            const campoCliente = document.getElementById('carga_rapida_cliente');
            const puedeCambiarPrecio = {{ $puedeCambiarPrecio ? 'true' : 'false' }};
            const MAXIMO_PRODUCTOS = 30;

            // Qué tipo eligió la última vez (Venta o Presupuesto) — se
            // recuerda entre productos distintos, como pidió el usuario,
            // para no tener que volver a tildarlo en cada uno.
            const storageKeyTipo = 'svm_carga_rapida_tipo_{{ auth()->user()->empresa_id }}';
            const storageKeySeleccion = 'svm_seleccion_productos_{{ auth()->user()->empresa_id }}';

            let avisoTimeout = null;

            function leerSeleccionGuardada() {
                try {
                    return new Set(JSON.parse(localStorage.getItem(storageKeySeleccion) || '[]'));
                } catch (e) {
                    return new Set();
                }
            }

            function tipoElegido() {
                return document.querySelector('input[name="carga_rapida_tipo"]:checked').value;
            }

            function cerrarModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            function mostrarFormularioNormal() {
                contenido.classList.remove('hidden');
                pregunta.classList.add('hidden');
                btnConfirmar.classList.remove('hidden');
            }

            function mostrarPregunta(texto) {
                contenido.classList.add('hidden');
                preguntaTexto.textContent = texto;
                pregunta.classList.remove('hidden');
                btnConfirmar.classList.add('hidden');
            }

            function mostrarAviso(texto) {
                clearTimeout(avisoTimeout);
                aviso.textContent = texto;
                aviso.classList.remove('hidden');
                avisoTimeout = setTimeout(function () {
                    aviso.classList.add('hidden');
                }, 4000);
            }

            function agregarFila(producto) {
                const fila = document.createElement('tr');
                fila.dataset.productoId = producto.id;

                const celdaNombre = document.createElement('td');
                celdaNombre.className = 'px-2 py-1.5';
                celdaNombre.textContent = producto.nombre;
                fila.appendChild(celdaNombre);

                const celdaCantidad = document.createElement('td');
                celdaCantidad.className = 'px-2 py-1.5';
                const inputCantidad = document.createElement('input');
                inputCantidad.type = 'number';
                inputCantidad.min = '1';
                inputCantidad.step = '1';
                inputCantidad.value = '1';
                inputCantidad.className = 'carga-rapida-cantidad w-20 rounded border border-slate-300 text-sm px-2 py-1 focus:border-slate-500 focus:ring-slate-500';
                celdaCantidad.appendChild(inputCantidad);
                fila.appendChild(celdaCantidad);

                if (puedeCambiarPrecio) {
                    const celdaPrecio = document.createElement('td');
                    celdaPrecio.className = 'px-2 py-1.5';
                    const inputPrecio = document.createElement('input');
                    inputPrecio.type = 'number';
                    inputPrecio.min = '0';
                    inputPrecio.step = '0.01';
                    inputPrecio.value = producto.precio;
                    inputPrecio.className = 'carga-rapida-precio w-24 rounded border border-slate-300 text-sm px-2 py-1 focus:border-slate-500 focus:ring-slate-500';
                    celdaPrecio.appendChild(inputPrecio);
                    fila.appendChild(celdaPrecio);
                }

                const celdaQuitar = document.createElement('td');
                celdaQuitar.className = 'px-2 py-1.5';
                const btnQuitar = document.createElement('button');
                btnQuitar.type = 'button';
                btnQuitar.className = 'text-xs text-red-600 hover:underline whitespace-nowrap';
                btnQuitar.textContent = 'Quitar';
                celdaQuitar.appendChild(btnQuitar);
                fila.appendChild(celdaQuitar);

                filas.appendChild(fila);
                inputCantidad.focus();
                inputCantidad.select();
            }

            // Delegado: cubre cualquier fila agregada, sin importar cuándo.
            filas.addEventListener('click', function (evento) {
                if (evento.target.tagName === 'BUTTON') {
                    evento.target.closest('tr').remove();
                }
            });

            async function abrirModalConProductos(idsAUsar, productoClickeado) {
                mostrarFormularioNormal();
                campoError.classList.add('hidden');
                campoCliente.value = '';
                filas.innerHTML = '';
                tabla.classList.add('hidden');
                cargando.classList.remove('hidden');
                titulo.textContent = idsAUsar.length === 1 ? productoClickeado.nombre : (idsAUsar.length + ' productos seleccionados');

                modal.classList.remove('hidden');
                modal.classList.add('flex');

                let productos;

                if (idsAUsar.length === 1) {
                    // El único caso donde no hace falta pedirle nada al
                    // servidor: ya se tienen nombre/precio en el data-* del
                    // botón que se clickeó.
                    productos = [{ id: productoClickeado.id, nombre: productoClickeado.nombre, precio: productoClickeado.precio }];
                } else {
                    try {
                        const cuerpo = new FormData();
                        cuerpo.append('_token', '{{ csrf_token() }}');
                        idsAUsar.forEach(function (id) { cuerpo.append('ids[]', id); });

                        const respuesta = await fetch('{{ route('productos.porIds') }}', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: cuerpo,
                        });
                        productos = await respuesta.json();
                    } catch (e) {
                        productos = [];
                    }
                }

                cargando.classList.add('hidden');
                tabla.classList.remove('hidden');
                productos.forEach(agregarFila);
            }

            try {
                const tipoGuardado = localStorage.getItem(storageKeyTipo);
                if (tipoGuardado) {
                    const radio = document.querySelector('input[name="carga_rapida_tipo"][value="' + tipoGuardado + '"]');
                    if (radio) radio.checked = true;
                }
            } catch (e) {}

            document.querySelectorAll('.btn-carga-rapida').forEach(function (boton) {
                boton.addEventListener('click', function () {
                    const productoClickeado = { id: boton.dataset.id, nombre: boton.dataset.nombre, precio: boton.dataset.precio };
                    const seleccionGuardada = leerSeleccionGuardada();

                    // Si el producto en el que se clickeó el carrito está
                    // tildado junto con otros, se trae toda la selección —
                    // si no, solo ese producto (comportamiento de siempre).
                    const idsAUsar = seleccionGuardada.size > 1 && seleccionGuardada.has(String(productoClickeado.id))
                        ? Array.from(seleccionGuardada)
                        : [productoClickeado.id];

                    if (idsAUsar.length > MAXIMO_PRODUCTOS) {
                        alert('Elegiste ' + idsAUsar.length + ' productos — como máximo se pueden cargar ' + MAXIMO_PRODUCTOS + ' de una vez por acá.');
                        return;
                    }

                    abrirModalConProductos(idsAUsar, productoClickeado);
                });
            });

            document.getElementById('btn_cerrar_carga_rapida').addEventListener('click', cerrarModal);
            document.getElementById('btn_cancelar_carga_rapida').addEventListener('click', cerrarModal);

            document.querySelectorAll('input[name="carga_rapida_tipo"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    try { localStorage.setItem(storageKeyTipo, radio.value); } catch (e) {}
                });
            });

            // accionPedido es null en el primer intento — si el servidor
            // contesta que ya hay un carrito/presupuesto en curso, se le
            // pregunta al usuario (mostrarPregunta) en vez de decidir solo;
            // los botones de esa pregunta vuelven a llamar acá con la
            // elección puesta, reenviando los mismos productos.
            async function enviarCarga(accionPedido) {
                const filasCargadas = Array.from(filas.querySelectorAll('tr'));

                if (filasCargadas.length === 0) {
                    mostrarFormularioNormal();
                    campoError.textContent = 'No queda ningún producto para agregar.';
                    campoError.classList.remove('hidden');
                    return;
                }

                const productos = [];
                for (const fila of filasCargadas) {
                    const cantidad = parseInt(fila.querySelector('.carga-rapida-cantidad').value, 10);

                    if (! cantidad || cantidad < 1) {
                        mostrarFormularioNormal();
                        campoError.textContent = 'Revisá la cantidad de "' + fila.children[0].textContent + '".';
                        campoError.classList.remove('hidden');
                        return;
                    }

                    const item = { producto_id: fila.dataset.productoId, cantidad: cantidad };
                    const campoPrecioFila = fila.querySelector('.carga-rapida-precio');
                    if (campoPrecioFila && campoPrecioFila.value !== '') {
                        item.precio_unitario = campoPrecioFila.value;
                    }
                    productos.push(item);
                }

                [btnConfirmar, btnUsarExistente, btnCrearNuevo].forEach(function (b) { b.disabled = true; });
                campoError.classList.add('hidden');

                const cuerpo = new FormData();
                cuerpo.append('_token', '{{ csrf_token() }}');
                cuerpo.append('tipo', tipoElegido());
                if (accionPedido) {
                    cuerpo.append('accion_pedido', accionPedido);
                }
                if (campoCliente.value.trim() !== '') {
                    cuerpo.append('cliente_nombre', campoCliente.value.trim());
                }
                productos.forEach(function (item, indice) {
                    cuerpo.append('productos[' + indice + '][producto_id]', item.producto_id);
                    cuerpo.append('productos[' + indice + '][cantidad]', item.cantidad);
                    if (item.precio_unitario !== undefined) {
                        cuerpo.append('productos[' + indice + '][precio_unitario]', item.precio_unitario);
                    }
                });

                try {
                    const respuesta = await fetch('{{ route('productos.cargaRapida') }}', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: cuerpo,
                    });
                    const datos = await respuesta.json().catch(() => null);

                    if (! respuesta.ok || ! datos) {
                        const mensajes = datos?.errors ? Object.values(datos.errors).flat() : null;
                        throw new Error(mensajes?.[0] || datos?.message || 'No se pudo agregar el producto.');
                    }

                    if (datos.necesitaEleccion) {
                        mostrarPregunta('Ya tenés ' + datos.etiquetaPedidoEnCurso + '. ¿Seguís cargando ahí o arrancás uno nuevo?');
                        return;
                    }

                    cerrarModal();
                    mostrarAviso(datos.mensaje);
                    // Se cargaron: no tiene sentido dejarlos tildados.
                    window.dispatchEvent(new CustomEvent('svm:carga-rapida-completada'));
                } catch (error) {
                    mostrarFormularioNormal();
                    campoError.textContent = error.message;
                    campoError.classList.remove('hidden');
                } finally {
                    [btnConfirmar, btnUsarExistente, btnCrearNuevo].forEach(function (b) { b.disabled = false; });
                    btnConfirmar.textContent = 'Agregar';
                }
            }

            btnConfirmar.addEventListener('click', function () {
                if (btnConfirmar.disabled) return;
                btnConfirmar.textContent = 'Agregando...';
                enviarCarga(null);
            });

            btnUsarExistente.addEventListener('click', function () { enviarCarga('usar_existente'); });
            btnCrearNuevo.addEventListener('click', function () { enviarCarga('crear_nuevo'); });
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

            const btnAjustarPrecioSeleccionados = document.getElementById('btn_ajustar_precio_seleccionados');
            const textoCantidadSeleccionados = document.getElementById('texto_cantidad_seleccionados');

            function actualizarBloque() {
                contador.textContent = seleccion.size;
                bloque.classList.toggle('hidden', seleccion.size === 0);

                if (textoCantidadSeleccionados) textoCantidadSeleccionados.textContent = seleccion.size;
                if (btnAjustarPrecioSeleccionados) btnAjustarPrecioSeleccionados.disabled = seleccion.size === 0;
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
            // check general puede tildar de verdad "todo el resultado", no
            // solo lo que se ve en pantalla. A propósito NO viene embebido en
            // la página: con catálogos de miles de productos eso pesaba
            // decenas de KB de JSON en CADA carga de Productos, se usara o no
            // este check — se pide por AJAX (productos.idsFiltrados) solo
            // cuando hace falta de verdad.
            const urlIdsFiltrados = {{ Js::from(route('productos.idsFiltrados', ['buscar' => $buscar, 'marca' => $marca, 'categoria' => $categoria, 'proveedor_id' => $proveedorId])) }};
            let idsAjustablesBusquedaCache = null;

            function obtenerIdsAjustablesBusqueda() {
                if (idsAjustablesBusquedaCache !== null) {
                    return Promise.resolve(idsAjustablesBusquedaCache);
                }

                return fetch(urlIdsFiltrados, { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (datos) {
                        idsAjustablesBusquedaCache = (datos.ids || []).map(String);
                        return idsAjustablesBusquedaCache;
                    })
                    .catch(function () { return []; });
            }

            const checkTodosPagina = document.getElementById('check_todos_pagina');
            if (checkTodosPagina) {
                // Refleja el estado real al cargar: si ya está TODO el
                // resultado de la búsqueda tildado (por ej. se tildó "todo"
                // en una página anterior), que no quede destildado a la
                // vista. Solo pide los ids si YA hay algo seleccionado —
                // entrando sin ninguna selección previa (el caso común) no
                // puede estar "todo tildado", así que no hace falta pedir
                // nada todavía.
                if (seleccion.size > 0) {
                    obtenerIdsAjustablesBusqueda().then(function (ids) {
                        checkTodosPagina.checked = ids.length > 0 && ids.every(function (id) { return seleccion.has(id); });
                    });
                }

                checkTodosPagina.addEventListener('change', function () {
                    const marcar = checkTodosPagina.checked;

                    obtenerIdsAjustablesBusqueda().then(function (ids) {
                        ids.forEach(function (id) {
                            if (marcar) {
                                seleccion.add(id);
                            } else {
                                seleccion.delete(id);
                            }
                        });

                        // Los checkboxes visibles de esta página reflejan el
                        // nuevo estado (los de otras páginas se van a ver
                        // tildados solos al navegar, porque leen de
                        // `seleccion` guardada en localStorage al cargar cada
                        // página).
                        document.querySelectorAll('.seleccion-producto').forEach(function (checkbox) {
                            checkbox.checked = seleccion.has(checkbox.dataset.id);
                        });

                        guardarSeleccion(seleccion);
                        actualizarBloque();
                    });
                });
            }

            function vaciarSeleccion() {
                seleccion = new Set();
                guardarSeleccion(seleccion);
                document.querySelectorAll('.seleccion-producto').forEach(function (cb) {
                    cb.checked = false;
                });
                if (checkTodosPagina) checkTodosPagina.checked = false;
                actualizarBloque();
            }

            document.getElementById('vaciar_seleccion').addEventListener('click', function (evento) {
                evento.preventDefault();
                vaciarSeleccion();
            });

            // Disparado por el modal de "agregar al carrito/presupuesto"
            // (ver btn-carga-rapida) al terminar: ya se cargaron, no tiene
            // sentido dejarlos tildados.
            window.addEventListener('svm:carga-rapida-completada', vaciarSeleccion);

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

        // Descarga por blob en vez de navegar directo a la URL: en la PWA
        // instalada en el celular, tanto window.location.href como un
        // <a target="_blank"> fallan en silencio (sin barra de navegador
        // real, no hay dónde "aterrizar" la descarga). Pidiendo el archivo
        // por fetch() y armando el link de descarga a partir del blob en
        // memoria sí funciona ahí — es el mismo archivo, solo cambia cómo
        // llega al navegador. De paso permite mostrar un error real si el
        // servidor responde con uno (ej. PDF con demasiados productos) en
        // vez de que la pestaña quede abierta sin hacer nada.
        function descargarArchivo(url, nombreArchivo, boton) {
            const textoOriginal = boton.textContent;
            boton.disabled = true;
            boton.textContent = 'Generando...';

            fetch(url, { credentials: 'same-origin' })
                .then(function (response) {
                    if (! response.ok) {
                        return response.json().catch(function () {
                            return {};
                        }).then(function (data) {
                            throw new Error(data.message || 'No se pudo generar el archivo.');
                        });
                    }

                    return response.blob();
                })
                .then(function (blob) {
                    const blobUrl = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = blobUrl;
                    link.download = nombreArchivo;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    setTimeout(function () {
                        URL.revokeObjectURL(blobUrl);
                    }, 1000);
                })
                .catch(function (error) {
                    alert(error.message || 'No se pudo descargar el archivo. Probá de nuevo.');
                })
                .finally(function () {
                    boton.disabled = false;
                    boton.textContent = textoOriginal;
                });
        }

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
                descargarArchivo('{{ route('productos.exportar', request()->only('buscar')) }}', 'productos.xlsx', confirmar);
            });
        })();

        (function () {
            const modal = document.getElementById('modal_exportar_pdf_productos');
            const mensaje = document.getElementById('modal_exportar_pdf_productos_mensaje');
            const cancelar = document.getElementById('modal_exportar_pdf_productos_cancelar');
            const confirmar = document.getElementById('modal_exportar_pdf_productos_confirmar');
            const storageKey = 'svm_seleccion_productos_{{ auth()->user()->empresa_id }}';

            // Si hay productos tildados (con el check general o a mano), el
            // PDF se arma con esos — si no, con el filtro actual. Mismo
            // criterio que ya usa el backend (exportarPdf prioriza
            // productos_ids sobre el filtro cuando vienen los dos).
            function idsSeleccionados() {
                try {
                    return JSON.parse(localStorage.getItem(storageKey) || '[]');
                } catch (e) {
                    return [];
                }
            }

            document.getElementById('btn_exportar_pdf_productos').addEventListener('click', function () {
                const ids = idsSeleccionados();

                mensaje.textContent = ids.length > 0
                    ? 'Se va a descargar un PDF listo para imprimir con los ' + ids.length + ' producto(s) que tenés seleccionados.'
                    : 'Se va a descargar un PDF listo para imprimir con los productos que coinciden con el filtro actual (búsqueda, marca, categoría y/o proveedor). Si son muchos productos (más de 1500), achicá el filtro primero.';

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

                const ids = idsSeleccionados();
                const params = new URLSearchParams();

                if (ids.length > 0) {
                    ids.forEach(function (id) { params.append('productos_ids[]', id); });
                } else {
                    if (@json($buscar)) params.append('buscar', @json($buscar));
                    if (@json($marca)) params.append('marca', @json($marca));
                    if (@json($categoria)) params.append('categoria', @json($categoria));
                    if (@json($proveedorId)) params.append('proveedor_id', @json($proveedorId));
                }

                descargarArchivo('{{ route('productos.exportarPdf') }}?' + params.toString(), 'productos.pdf', confirmar);
            });
        })();

        (function () {
            const reposicionParaEditar = @json($reposicionParaEditar);
            const urlBuscarParaReponer = {{ Js::from(route('productos.buscarParaReponer')) }};

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
            let timerDebounce = null;
            let idPeticion = 0;
            const agregados = new Map(); // producto_id -> { inputCantidad }

            // Búsqueda en el servidor en vez de filtrar un catálogo entero ya
            // cargado en la página — mismo criterio que ya usa el buscador de
            // Carritos/Caja (ver carritos/show.blade.php): con una empresa de
            // miles de productos, mandar todo el catálogo en cada carga de
            // esta pantalla pesaba varios cientos de KB, se abriera o no este
            // modal. idPeticion descarta la respuesta si ya se tipeó algo más
            // nuevo para cuando llega.
            function buscarEnServidor(texto) {
                const miId = ++idPeticion;

                return fetch(urlBuscarParaReponer + '?q=' + encodeURIComponent(texto), {
                    headers: { 'Accept': 'application/json' },
                })
                    .then((respuesta) => respuesta.json())
                    .then((datos) => (miId === idPeticion ? datos : null))
                    .catch(() => null);
            }

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
                    agregarProducto({
                        id: item.producto_id,
                        nombre: item.nombre,
                        codigo: item.codigo,
                        stock: item.stock,
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
                const texto = input.value.trim();
                indiceActivo = -1;
                clearTimeout(timerDebounce);

                if (texto === '') {
                    resultados = [];
                    renderizarResultados();
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
                        renderizarResultados();
                    });
                }, 200);
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
