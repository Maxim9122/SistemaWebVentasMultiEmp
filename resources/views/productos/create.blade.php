@extends('layouts.app')

@section('titulo', 'Nuevo producto')

@section('contenido')
    <div class="max-w-lg bg-white rounded-lg shadow p-6">
        <button type="button" id="btn_escanear_codigo" class="mb-4 inline-flex items-center gap-1.5 rounded border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                <path d="M5 7V5a1 1 0 0 1 1-1h2M17 4h2a1 1 0 0 1 1 1v2M19 17v2a1 1 0 0 1-1 1h-2M7 20H5a1 1 0 0 1-1-1v-2"/>
                <path d="M4 12h16"/>
            </svg>
            Escanear código de barras
        </button>

        <div id="escaner_codigo" class="hidden mb-4 rounded border border-slate-200 bg-slate-50 p-4">
            <div id="escaner_camara_wrap" class="mb-3 overflow-hidden rounded bg-black min-h-[200px] flex items-center justify-center"></div>
            <p id="escaner_estado" class="text-sm text-slate-500 mb-2">Apuntá la cámara al código de barras...</p>
            <div class="flex items-center gap-2 mb-2">
                <input type="text" id="escaner_codigo_manual" placeholder="o escribí el código a mano"
                    class="flex-1 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                <button type="button" id="escaner_buscar_manual" class="rounded bg-slate-900 text-white px-3 py-2 text-sm font-medium hover:bg-slate-800">
                    Buscar
                </button>
            </div>
            <button type="button" id="escaner_cerrar" class="text-sm text-slate-500 hover:underline">Cerrar</button>
        </div>

        <div id="aviso_codigo_existente" class="hidden mb-4 rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800"></div>

        <form method="POST" action="{{ route('productos.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" id="escaneo_continuo" name="escaneo_continuo" value="0">

            <div>
                <label for="nombre" class="block text-sm font-medium mb-1">Nombre</label>
                <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" required
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="codigo" class="block text-sm font-medium mb-1">Código / SKU</label>
                    <input id="codigo" name="codigo" type="text" value="{{ old('codigo') }}"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label for="categoria" class="block text-sm font-medium mb-1">Categoría</label>
                    <input id="categoria" name="categoria" type="text" value="{{ old('categoria') }}"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
            </div>

            <div>
                <label for="marca" class="block text-sm font-medium mb-1">Marca</label>
                <input id="marca" name="marca" type="text" value="{{ old('marca') }}"
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="precio" class="block text-sm font-medium mb-1">Precio</label>
                    <input id="precio" name="precio" type="number" step="0.01" min="0" value="{{ old('precio') }}" required
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label for="costo" class="block text-sm font-medium mb-1">Costo</label>
                    <input id="costo" name="costo" type="number" step="0.01" min="0" value="{{ old('costo') }}"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                @if (auth()->user()->empresa->controla_stock)
                    <div>
                        <label for="stock" class="block text-sm font-medium mb-1">Stock</label>
                        <input id="stock" name="stock" type="number" step="1" min="0" value="{{ old('stock', 0) }}"
                            class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                @else
                    <input type="hidden" name="stock" value="0">
                @endif
                <div class="@if (! auth()->user()->empresa->controla_stock) col-span-2 @endif">
                    <label for="unidad" class="block text-sm font-medium mb-1">Unidad</label>
                    <input id="unidad" name="unidad" type="text" value="{{ old('unidad') }}" placeholder="kg, unidad, caja..."
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="proveedor_id" class="block text-sm font-medium">Proveedor</label>
                    <a href="{{ route('productos.proveedores.create') }}" target="_blank" class="text-xs text-slate-500 hover:underline">+ Nuevo proveedor</a>
                </div>
                <select id="proveedor_id" name="proveedor_id"
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    <option value="">— Sin proveedor —</option>
                    @foreach ($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}" @selected(old('proveedor_id') == $proveedor->id)>{{ $proveedor->nombre }}</option>
                    @endforeach
                </select>
            </div>

            @include('partials.campos-precio-por-cantidad', ['producto' => null])

            <div class="flex gap-2">
                <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Crear producto
                </button>
                <a href="{{ route('productos.index') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const btnAbrir = document.getElementById('btn_escanear_codigo');
            const panel = document.getElementById('escaner_codigo');
            const camaraWrap = document.getElementById('escaner_camara_wrap');
            const estado = document.getElementById('escaner_estado');
            const inputManual = document.getElementById('escaner_codigo_manual');
            const btnBuscarManual = document.getElementById('escaner_buscar_manual');
            const btnCerrar = document.getElementById('escaner_cerrar');
            const avisoExistente = document.getElementById('aviso_codigo_existente');
            const inputEscaneoContinuo = document.getElementById('escaneo_continuo');

            const inputNombre = document.getElementById('nombre');
            const inputCodigo = document.getElementById('codigo');
            const inputMarca = document.getElementById('marca');
            const inputCategoria = document.getElementById('categoria');

            const token = document.querySelector('input[name="_token"]').value;
            const urlBuscar = {{ Js::from(route('productos.buscarCodigoBarra')) }};

            let stream = null;
            let detector = null;
            let html5QrCode = null;
            let escaneando = false;
            let buscandoCodigo = false;

            function rellenarSiVacio(input, valor) {
                if (valor && !input.value.trim()) {
                    input.value = valor;
                }
            }

            function aplicarResultado(codigo, datos) {
                rellenarSiVacio(inputCodigo, codigo);

                if (datos.existe_en_mi_empresa) {
                    avisoExistente.innerHTML = 'Ya tenés este código cargado como "' + datos.producto_existente.nombre +
                        '" — <a href="/productos/' + datos.producto_existente.id + '/editar" class="underline">ir a editarlo</a> en vez de crear uno nuevo.';
                    avisoExistente.classList.remove('hidden');

                    return;
                }

                avisoExistente.classList.add('hidden');

                rellenarSiVacio(inputNombre, datos.nombre);
                rellenarSiVacio(inputMarca, datos.marca);
                rellenarSiVacio(inputCategoria, datos.categoria);
            }

            function buscarCodigo(codigo) {
                codigo = (codigo || '').trim();

                if (! codigo || buscandoCodigo) {
                    return;
                }

                buscandoCodigo = true;
                inputEscaneoContinuo.value = '1';
                estado.textContent = 'Buscando...';

                fetch(urlBuscar, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({codigo: codigo}),
                })
                    .then((respuesta) => respuesta.json())
                    .then((datos) => {
                        aplicarResultado(codigo, datos);
                        detenerCamara();
                        cerrarPanel();
                    })
                    .catch(() => {
                        estado.textContent = 'No se pudo buscar — revisá el código o cargalo a mano.';
                        buscandoCodigo = false;
                    });
            }

            function vibrarSiSePuede() {
                if (navigator.vibrate) {
                    navigator.vibrate(120);
                }
            }

            function detectarLoop(video) {
                if (! escaneando) {
                    return;
                }

                detector.detect(video)
                    .then((codigos) => {
                        if (codigos.length > 0) {
                            vibrarSiSePuede();
                            buscarCodigo(codigos[0].rawValue);

                            return;
                        }

                        requestAnimationFrame(() => detectarLoop(video));
                    })
                    .catch(() => requestAnimationFrame(() => detectarLoop(video)));
            }

            function leerConBarcodeDetector() {
                const video = document.createElement('video');
                video.setAttribute('muted', '');
                video.setAttribute('playsinline', '');
                video.className = 'w-full';
                camaraWrap.innerHTML = '';
                camaraWrap.appendChild(video);

                detector = new BarcodeDetector({formats: ['ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128']});

                navigator.mediaDevices.getUserMedia({video: {facingMode: 'environment'}})
                    .then((s) => {
                        stream = s;
                        video.srcObject = s;
                        video.play();
                        escaneando = true;
                        detectarLoop(video);
                    })
                    .catch(() => {
                        estado.textContent = 'No se pudo abrir la cámara — revisá el permiso o cargá el código a mano.';
                    });
            }

            function leerConHtml5Qrcode() {
                estado.textContent = 'Cargando lector de cámara...';

                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js';

                script.onload = function () {
                    estado.textContent = 'Apuntá la cámara al código de barras...';
                    html5QrCode = new Html5Qrcode('escaner_camara_wrap');
                    escaneando = true;

                    html5QrCode.start(
                        {facingMode: 'environment'},
                        {fps: 10, qrbox: 220},
                        function (codigoLeido) {
                            vibrarSiSePuede();
                            buscarCodigo(codigoLeido);
                        }
                    ).catch(function () {
                        estado.textContent = 'No se pudo abrir la cámara — revisá el permiso o cargá el código a mano.';
                    });
                };

                script.onerror = function () {
                    estado.textContent = 'No se pudo cargar el lector de cámara — cargá el código a mano.';
                };

                document.body.appendChild(script);
            }

            function abrirCamara() {
                estado.textContent = 'Apuntá la cámara al código de barras...';

                if ('BarcodeDetector' in window) {
                    leerConBarcodeDetector();
                } else {
                    leerConHtml5Qrcode();
                }
            }

            function detenerCamara() {
                escaneando = false;
                buscandoCodigo = false;

                if (stream) {
                    stream.getTracks().forEach((track) => track.stop());
                    stream = null;
                }

                if (html5QrCode) {
                    html5QrCode.stop().then(() => html5QrCode.clear()).catch(() => {});
                    html5QrCode = null;
                } else {
                    camaraWrap.innerHTML = '';
                }
            }

            function abrirPanel() {
                panel.classList.remove('hidden');
                abrirCamara();
            }

            function cerrarPanel() {
                detenerCamara();
                panel.classList.add('hidden');
            }

            btnAbrir.addEventListener('click', function () {
                if (panel.classList.contains('hidden')) {
                    abrirPanel();
                } else {
                    cerrarPanel();
                }
            });

            btnCerrar.addEventListener('click', cerrarPanel);

            btnBuscarManual.addEventListener('click', function () {
                buscarCodigo(inputManual.value);
                inputManual.value = '';
            });

            inputManual.addEventListener('keydown', function (evento) {
                if (evento.key === 'Enter') {
                    evento.preventDefault();
                    buscarCodigo(inputManual.value);
                    inputManual.value = '';
                }
            });

            @if ($abrirEscaner)
                abrirPanel();
            @endif
        })();
    </script>
@endsection
