@extends('layouts.app')

@section('titulo', 'Importar productos desde un PDF')

@section('contenido')
    <div id="panel-subir-pdf" class="max-w-lg bg-white rounded-lg shadow p-6">
        <p class="text-sm text-slate-600 mb-4">
            Subí un PDF de tu lista de precios (hasta 30 páginas) — el sistema convierte cada página en una imagen y
            la lee igual que si fuera una foto. En el siguiente paso vas a poder revisar y corregir lo que haga falta
            antes de importar nada.
        </p>

        <div class="mb-4 rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Esto usa un servicio externo de lectura de imágenes (tiene un costo chico por página, a cargo del
            sistema) — un PDF de varias páginas tarda un poco más que una sola foto.
        </div>

        <form id="form_subir_pdf" method="POST" action="{{ route('productos.importarPdf.convertir') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label for="pdf" class="block text-sm font-medium mb-1">PDF</label>
                <input id="pdf" name="pdf" type="file" accept="application/pdf" required
                    class="w-full text-sm rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                <p class="text-xs text-slate-400 mt-1">Máximo 15 MB, hasta 30 páginas.</p>
                @error('pdf')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
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
                <p class="text-xs text-slate-400 mt-1">Se va a asignar a todos los productos creados o actualizados en esta importación.</p>
            </div>

            <div class="flex gap-2">
                <button type="submit" id="btn_leer_pdf" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Leer PDF
                </button>
                <a href="{{ route('productos.index') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    {{-- Reemplaza al panel de subida mientras se procesa, página por página,
    para no mandar todo el PDF a leer en un solo pedido (eso era lo que se
    iba al timeout del servidor con varias páginas). --}}
    <div id="panel-progreso-pdf" class="hidden max-w-lg bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-3">Leyendo el PDF...</h2>
        <div class="w-full bg-slate-200 rounded-full h-2.5 mb-2 overflow-hidden">
            <div id="barra-progreso-pdf" class="bg-slate-900 h-2.5 rounded-full transition-all" style="width: 0%"></div>
        </div>
        <p id="texto-progreso-pdf" class="text-sm text-slate-600">Convirtiendo el PDF...</p>
        <p class="text-xs text-slate-400 mt-2">No cierres ni recargues esta página hasta que termine.</p>
    </div>

    <div id="panel-error-pdf" class="hidden max-w-lg bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold mb-3 text-red-700">Hubo un problema</h2>
        <p id="texto-error-pdf" class="text-sm text-slate-600 mb-4"></p>
        <button type="button" onclick="location.reload()" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
            Volver a intentar
        </button>
    </div>

    {{-- Form oculto: una vez juntados los productos de todas las páginas,
    este sí es un submit tradicional de página completa — no llama a
    ningún servicio externo, solo arma la pantalla de revisión, así que no
    hay riesgo de timeout acá. --}}
    <form id="form_revisar_pdf" method="POST" action="{{ route('productos.importarPdf.revisar') }}" class="hidden">
        @csrf
        <input type="hidden" name="productos_json" id="productos_json_pdf">
        <input type="hidden" name="proveedor_id" id="proveedor_id_pdf">
    </form>

    <script>
        (function () {
            const formSubir = document.getElementById('form_subir_pdf');
            const panelSubir = document.getElementById('panel-subir-pdf');
            const panelProgreso = document.getElementById('panel-progreso-pdf');
            const panelError = document.getElementById('panel-error-pdf');
            const barraProgreso = document.getElementById('barra-progreso-pdf');
            const textoProgreso = document.getElementById('texto-progreso-pdf');

            formSubir.addEventListener('submit', async function (evento) {
                evento.preventDefault();

                const boton = document.getElementById('btn_leer_pdf');
                if (boton.disabled) {
                    return;
                }
                boton.disabled = true;

                panelSubir.classList.add('hidden');
                panelProgreso.classList.remove('hidden');

                // Avisa con el cartel nativo del navegador si el usuario
                // intenta cerrar la pestaña, recargar o navegar a otra
                // página mientras se está leyendo el PDF (por error o a
                // propósito) — se saca apenas termina, ya sea bien o mal.
                function avisarAntesDeSalir(evento) {
                    evento.preventDefault();
                    evento.returnValue = '';
                }
                window.addEventListener('beforeunload', avisarAntesDeSalir);

                const proveedorId = document.getElementById('proveedor_id').value;

                try {
                    // Paso 1: convertir el PDF a imágenes (rápido, Imagick) —
                    // todavía no se lee ninguna página con el servicio de IA.
                    const cuerpoConvertir = new FormData(formSubir);
                    const respuestaConvertir = await fetch(formSubir.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: cuerpoConvertir,
                    });
                    const datosConvertir = await respuestaConvertir.json().catch(() => null);

                    if (! respuestaConvertir.ok || ! datosConvertir) {
                        const mensajes = datosConvertir?.errors ? Object.values(datosConvertir.errors).flat() : null;
                        throw new Error(mensajes?.[0] || datosConvertir?.mensaje || 'No se pudo leer el PDF.');
                    }

                    const { token, totalPaginas } = datosConvertir;

                    // Paso 2: pedir la lectura de cada página, una atrás de la
                    // otra — nunca se manda más de una página por pedido, así
                    // que nunca se acerca al límite de tiempo del servidor sin
                    // importar cuántas páginas tenga el PDF.
                    const productosAcumulados = [];

                    for (let pagina = 0; pagina < totalPaginas; pagina++) {
                        textoProgreso.textContent = `Leyendo página ${pagina + 1} de ${totalPaginas}...`;
                        barraProgreso.style.width = Math.round((pagina / totalPaginas) * 100) + '%';

                        const cuerpoPagina = new FormData();
                        cuerpoPagina.append('_token', formSubir.querySelector('input[name="_token"]').value);
                        cuerpoPagina.append('token', token);
                        cuerpoPagina.append('pagina', pagina);

                        const respuestaPagina = await fetch('{{ route('productos.importarPdf.extraerPagina') }}', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: cuerpoPagina,
                        });
                        const datosPagina = await respuestaPagina.json().catch(() => null);

                        if (! respuestaPagina.ok || ! datosPagina) {
                            throw new Error(datosPagina?.mensaje || `No se pudo leer la página ${pagina + 1}.`);
                        }

                        productosAcumulados.push(...datosPagina.productos);
                    }

                    barraProgreso.style.width = '100%';
                    textoProgreso.textContent = 'Terminando...';

                    if (productosAcumulados.length === 0) {
                        throw new Error('No se detectó ningún producto en el PDF. Probá con otro archivo más claro.');
                    }

                    // Paso 3: con todo ya junto, un submit normal de página
                    // completa a la pantalla de revisión — se saca el aviso
                    // antes, porque esta navegación es la salida esperada.
                    window.removeEventListener('beforeunload', avisarAntesDeSalir);
                    document.getElementById('productos_json_pdf').value = JSON.stringify(productosAcumulados);
                    document.getElementById('proveedor_id_pdf').value = proveedorId;
                    document.getElementById('form_revisar_pdf').submit();
                } catch (error) {
                    window.removeEventListener('beforeunload', avisarAntesDeSalir);
                    panelProgreso.classList.add('hidden');
                    panelError.classList.remove('hidden');
                    document.getElementById('texto-error-pdf').textContent = error.message;
                }
            });
        })();
    </script>
@endsection
