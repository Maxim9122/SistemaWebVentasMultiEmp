@extends('layouts.app')

@section('titulo', 'Confirmar importación')

@php
    $etiquetas = [
        'nombre' => 'Nombre',
        'codigo' => 'Código / SKU',
        'precio' => 'Precio',
        'costo' => 'Costo',
        'stock' => 'Stock',
        'categoria' => 'Categoría',
        'marca' => 'Marca',
        'unidad' => 'Unidad',
    ];
@endphp

@section('contenido')
    <div id="panel-mapeo-importacion" class="bg-white rounded-lg shadow p-6">
        <p class="text-sm text-slate-600 mb-4">
            Revisá que cada columna del Excel esté asignada al campo correcto. Ya propusimos una asignación —
            ajustá lo que haga falta. La próxima vez que subas un Excel con estos mismos encabezados, no vas a
            tener que hacer esto de nuevo.
        </p>

        @if (! empty($codigosInvalidos))
            <div class="rounded border border-sky-300 bg-sky-50 px-4 py-3 mb-4">
                <p class="text-sm font-medium text-sky-900 mb-1">
                    ℹ {{ array_sum($codigosInvalidos) }} {{ Str::plural('fila', array_sum($codigosInvalidos)) }} de este Excel
                    {{ array_sum($codigosInvalidos) === 1 ? 'tiene' : 'tienen' }} un código no válido (no es un código real, es un
                    valor de error de Excel) — se van a crear como productos nuevos <strong>sin código</strong>, sin buscar
                    ninguna coincidencia por ese valor.
                </p>
                <p class="text-xs text-sky-800">
                    @foreach ($codigosInvalidos as $valor => $cantidad)
                        "{{ $valor }}" → {{ $cantidad }} {{ Str::plural('fila', $cantidad) }}{{ ! $loop->last ? ', ' : '' }}
                    @endforeach
                </p>
            </div>
        @endif

        @if ($duplicados->isNotEmpty())
            @php
                $filasAfectadas = $duplicados->sum('veces');
                $codigoSospechoso = $duplicados->first(fn ($d) => $d['veces'] >= 15);
            @endphp

            @if ($codigoSospechoso)
                <div class="rounded border border-red-300 bg-red-50 px-4 py-3 mb-4">
                    <p class="text-sm font-medium text-red-900 mb-1">
                        🔴 El código "{{ $codigoSospechoso['codigo'] }}" aparece en {{ $codigoSospechoso['veces'] }} filas distintas del Excel.
                    </p>
                    <p class="text-xs text-red-800">
                        Esto normalmente pasa cuando la columna "Código" quedó mal exportada (por ejemplo, valores
                        vacíos o con error que Excel muestra como "NaN" o "#N/A"). Si seguís así, vas a terminar
                        creando o actualizando el mismo producto una y otra vez por error, en vez de {{ $filasAfectadas }}
                        productos distintos. Te recomendamos revisar el archivo, o directamente dejar la columna
                        "Código" en "Ignorar esta columna" más abajo si no la vas a usar.
                    </p>
                </div>
            @endif

            <div class="rounded border border-amber-300 bg-amber-50 px-4 py-3 mb-4">
                <p class="text-sm font-medium text-amber-900 mb-1">
                    ⚠ {{ $filasAfectadas }} {{ Str::plural('fila', $filasAfectadas) }} de este Excel
                    ({{ $duplicados->count() }} {{ Str::plural('código', $duplicados->count()) }} distinto{{ $duplicados->count() === 1 ? '' : 's' }})
                    ya {{ $filasAfectadas === 1 ? 'existe' : 'existen' }} en tu catálogo (mismo código).
                </p>
                <p class="text-xs text-amber-800 mb-2">
                    Según cómo esté asignada la columna "Código" ahora mismo — si la cambiás abajo, puede variar.
                </p>
                <details class="text-xs text-amber-800">
                    <summary class="cursor-pointer hover:underline">Ver cuáles ({{ $duplicados->count() }})</summary>
                    <ul class="list-disc list-inside mt-1 space-y-0.5 max-h-32 overflow-y-auto">
                        @foreach ($duplicados as $duplicado)
                            <li>
                                Código {{ $duplicado['codigo'] }} — ya cargado como "{{ $duplicado['nombre_actual'] }}"
                                @if ($duplicado['nombre_excel'] && $duplicado['nombre_excel'] !== $duplicado['nombre_actual'])
                                    (en el Excel: "{{ $duplicado['nombre_excel'] }}")
                                @endif
                                @if ($duplicado['veces'] > 1)
                                    — se repite en {{ $duplicado['veces'] }} filas del Excel
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </details>
            </div>
        @endif

        <form method="POST" action="{{ route('productos.importar.confirmar') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            @if ($duplicados->isNotEmpty())
                <div class="rounded border border-slate-200 p-3 mb-4">
                    <p class="text-sm font-medium mb-2">¿Qué hacer con los productos repetidos?</p>
                    <label class="flex items-start gap-2 text-sm mb-1.5 cursor-pointer">
                        <input type="radio" name="modo_duplicados" value="actualizar" checked class="mt-0.5">
                        <span><strong>Actualizarlos</strong> — pisa precio, costo, stock, etc. con los datos del Excel.</span>
                    </label>
                    <label class="flex items-start gap-2 text-sm mb-1.5 cursor-pointer">
                        <input type="radio" name="modo_duplicados" value="ignorar" class="mt-0.5">
                        <span><strong>Ignorarlos</strong> — los deja tal cual están, solo crea los que son nuevos.</span>
                    </label>
                    <label class="flex items-start gap-2 text-sm cursor-pointer">
                        <input type="radio" name="modo_duplicados" value="copiar" class="mt-0.5">
                        <span><strong>Crearlos como copia nueva</strong> — no toca el producto existente, crea uno nuevo con los datos del Excel pero sin código (para no chocar con el que ya existe).</span>
                    </label>
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-sm mb-4">
                    <thead class="bg-slate-50 text-slate-500 text-left">
                        <tr>
                            <th class="px-3 py-2 font-medium">Columna del Excel</th>
                            <th class="px-3 py-2 font-medium">Ejemplo</th>
                            <th class="px-3 py-2 font-medium">Se importa como</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($encabezados as $indice => $encabezado)
                            <tr>
                                <td class="px-3 py-2 font-medium">{{ $encabezado !== '' ? $encabezado : '(sin nombre)' }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ $preview[0][$indice] ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    <select name="mapeo[{{ $indice }}]" class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                                        <option value="">Ignorar esta columna</option>
                                        @foreach ($campos as $campo)
                                            <option value="{{ $campo }}" @selected(($sugerencias[$indice] ?? null) === $campo)>
                                                {{ $etiquetas[$campo] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex gap-2">
                <button type="button" id="abrir-modal-importar" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Importar
                </button>
                <a href="{{ route('productos.importar.subir') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    <div id="modal-importar" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div id="modal-importar-confirmacion">
                <h2 class="text-lg font-semibold mb-3">Confirmar importación</h2>

                <ul class="text-sm text-slate-600 space-y-2 mb-4">
                    <li>Se van a procesar <strong>{{ $filasTotales }}</strong> {{ Str::plural('fila', $filasTotales) }} del archivo.</li>
                    <li id="texto-modo-duplicados">Los productos cuyo código ya exista se van a <strong>actualizar</strong>; el resto se va a <strong>crear</strong>.</li>
                    <li>
                        Proveedor asignado:
                        <strong>{{ $proveedorNombre ?? 'ninguno' }}</strong>
                    </li>
                </ul>

                <p class="text-xs text-slate-400 mb-4">Esta acción queda registrada en el historial y se puede deshacer después desde "Importaciones".</p>

                <div class="flex gap-2 justify-end">
                    <button type="button" id="cancelar-modal-importar" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                        Cancelar
                    </button>
                    <button type="button" id="confirmar-modal-importar" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                        Confirmar importación
                    </button>
                </div>
            </div>

            <div id="modal-importar-progreso" class="hidden">
                <h2 class="text-lg font-semibold mb-3">Importando...</h2>
                <div class="w-full bg-slate-200 rounded-full h-2.5 mb-2 overflow-hidden">
                    <div id="barra-progreso-importar" class="bg-slate-900 h-2.5 rounded-full transition-all" style="width: 0%"></div>
                </div>
                <p id="texto-progreso-importar" class="text-sm text-slate-600">Procesando...</p>
                <p class="text-xs text-slate-400 mt-2">No cierres ni recargues esta página hasta que termine.</p>
            </div>

            <div id="modal-importar-error" class="hidden">
                <h2 class="text-lg font-semibold mb-3 text-red-700">Hubo un problema</h2>
                <p id="texto-error-importar" class="text-sm text-slate-600 mb-4"></p>
                <div class="flex justify-end">
                    <button type="button" onclick="location.reload()" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                        Volver a intentar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Mismo contenido que productos/importar/resultado.blade.php, pero
    rellenado por JS al terminar todos los lotes — evita un redirect final
    que perdería la lista de errores acumulada en el navegador (no se
    persiste en la base, solo viaja de a poco en cada respuesta de lote). --}}
    <div id="panel-resultado-importacion" class="hidden bg-white rounded-lg shadow p-6 space-y-4">
        <div class="flex gap-6 text-sm">
            <div>
                <p class="text-2xl font-semibold text-emerald-700" id="resultado-creados">0</p>
                <p class="text-slate-500">Creados</p>
            </div>
            <div>
                <p class="text-2xl font-semibold text-sky-700" id="resultado-actualizados">0</p>
                <p class="text-slate-500">Actualizados</p>
            </div>
            <div id="resultado-ignorados-contenedor" class="hidden">
                <p class="text-2xl font-semibold text-slate-500" id="resultado-ignorados">0</p>
                <p class="text-slate-500">Ignorados (ya existían)</p>
            </div>
            <div id="resultado-copiados-contenedor" class="hidden">
                <p class="text-2xl font-semibold text-indigo-700" id="resultado-copiados">0</p>
                <p class="text-slate-500">Copiados (sin código)</p>
            </div>
            <div>
                <p class="text-2xl font-semibold text-red-700" id="resultado-con-error">0</p>
                <p class="text-slate-500">Con error</p>
            </div>
        </div>

        <p id="resultado-aviso-codigos-invalidos" class="hidden text-xs text-sky-800 bg-sky-50 border border-sky-200 rounded px-3 py-2"></p>

        <div id="resultado-errores-contenedor" class="hidden rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-medium mb-1">Filas que no se pudieron importar:</p>
            <ul id="resultado-lista-errores" class="list-disc list-inside space-y-0.5 max-h-64 overflow-y-auto"></ul>
        </div>

        <div class="flex gap-2 pt-2">
            <a href="{{ route('productos.index') }}" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                Ver productos
            </a>
            <a href="{{ route('productos.importar.subir') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                Importar otro archivo
            </a>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('modal-importar');
            const form = document.querySelector('form[action="{{ route('productos.importar.confirmar') }}"]');

            const textoModoDuplicados = document.getElementById('texto-modo-duplicados');
            const radiosModoDuplicados = document.querySelectorAll('input[name="modo_duplicados"]');

            document.getElementById('abrir-modal-importar').addEventListener('click', function () {
                if (textoModoDuplicados && radiosModoDuplicados.length > 0) {
                    const elegido = document.querySelector('input[name="modo_duplicados"]:checked');
                    const textos = {
                        ignorar: 'Los productos cuyo código ya exista se van a <strong>dejar como están</strong>; el resto se va a <strong>crear</strong>.',
                        copiar: 'Los productos cuyo código ya exista se van a <strong>duplicar</strong> (copia nueva sin código); el resto se va a <strong>crear</strong>.',
                        actualizar: 'Los productos cuyo código ya exista se van a <strong>actualizar</strong>; el resto se va a <strong>crear</strong>.',
                    };
                    textoModoDuplicados.innerHTML = textos[elegido?.value] ?? textos.actualizar;
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });

            document.getElementById('cancelar-modal-importar').addEventListener('click', function () {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });

            // En vez de mandar el Excel entero en un solo pedido (un archivo de
            // miles de filas podía superar el timeout del servidor y dejar al
            // usuario con la pantalla en blanco — ver confirmar()/procesarLote()
            // en ProductoImportController), se procesa en tandas chicas, una
            // atrás de la otra, mostrando el progreso. Nunca se topa con ese
            // límite sin importar cuán grande sea el archivo.
            document.getElementById('confirmar-modal-importar').addEventListener('click', function (evento) {
                const boton = evento.currentTarget;

                if (boton.disabled) {
                    return;
                }

                boton.disabled = true;
                document.getElementById('cancelar-modal-importar').disabled = true;
                document.getElementById('modal-importar-confirmacion').classList.add('hidden');
                document.getElementById('modal-importar-progreso').classList.remove('hidden');

                procesarImportacionPorTandas();
            });

            // Avisa con el cartel nativo del navegador si el usuario intenta
            // cerrar la pestaña, recargar o navegar a otra página mientras
            // la importación está corriendo (por error o a propósito) — se
            // activa solo durante el proceso, no en el resto de la pantalla.
            function avisarAntesDeSalir(evento) {
                evento.preventDefault();
                evento.returnValue = '';
            }

            async function procesarImportacionPorTandas() {
                window.addEventListener('beforeunload', avisarAntesDeSalir);

                const csrfToken = form.querySelector('input[name="_token"]').value;
                const token = form.querySelector('input[name="token"]').value;
                const modoDuplicadosElegido = document.querySelector('input[name="modo_duplicados"]:checked');

                const barraProgreso = document.getElementById('barra-progreso-importar');
                const textoProgreso = document.getElementById('texto-progreso-importar');

                let offset = 0;
                let importacionId = null;
                let terminado = false;
                let total = {{ (int) $filasTotales }};

                const acumulado = { creados: 0, actualizados: 0, ignorados: 0, copiados: 0, codigosInvalidos: 0, errores: [] };

                try {
                    while (! terminado) {
                        const cuerpo = new FormData();
                        cuerpo.append('_token', csrfToken);
                        cuerpo.append('token', token);
                        form.querySelectorAll('select[name^="mapeo["]').forEach(function (select) {
                            cuerpo.append(select.name, select.value);
                        });
                        if (modoDuplicadosElegido) {
                            cuerpo.append('modo_duplicados', modoDuplicadosElegido.value);
                        }
                        cuerpo.append('offset', offset);
                        if (importacionId !== null) {
                            cuerpo.append('importacion_id', importacionId);
                        }

                        const respuesta = await fetch('{{ route('productos.importar.procesarLote') }}', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: cuerpo,
                        });

                        const datos = await respuesta.json().catch(() => null);

                        if (! respuesta.ok || ! datos) {
                            const mensajes = datos?.errors ? Object.values(datos.errors).flat() : null;
                            throw new Error(mensajes?.[0] || datos?.mensaje || 'No se pudo completar la importación.');
                        }

                        importacionId = datos.importacion_id;
                        offset = datos.offset_siguiente;
                        total = datos.total;
                        terminado = datos.terminado;

                        acumulado.creados += datos.creados;
                        acumulado.actualizados += datos.actualizados;
                        acumulado.ignorados += datos.ignorados;
                        acumulado.copiados += datos.copiados;
                        acumulado.codigosInvalidos += datos.codigosInvalidos;
                        acumulado.errores.push(...datos.errores);

                        const procesadas = Math.min(offset, total);
                        const porcentaje = total > 0 ? Math.round((procesadas / total) * 100) : 100;
                        barraProgreso.style.width = porcentaje + '%';
                        textoProgreso.textContent = `Procesando ${procesadas} de ${total}...`;
                    }

                    mostrarResultadoFinal(acumulado);
                } catch (error) {
                    document.getElementById('modal-importar-progreso').classList.add('hidden');
                    document.getElementById('modal-importar-error').classList.remove('hidden');
                    document.getElementById('texto-error-importar').textContent = error.message;
                } finally {
                    window.removeEventListener('beforeunload', avisarAntesDeSalir);
                }
            }

            function mostrarResultadoFinal(resultado) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.getElementById('panel-mapeo-importacion').classList.add('hidden');

                document.getElementById('resultado-creados').textContent = resultado.creados;
                document.getElementById('resultado-actualizados').textContent = resultado.actualizados;
                document.getElementById('resultado-con-error').textContent = resultado.errores.length;

                if (resultado.ignorados > 0) {
                    document.getElementById('resultado-ignorados').textContent = resultado.ignorados;
                    document.getElementById('resultado-ignorados-contenedor').classList.remove('hidden');
                }

                if (resultado.copiados > 0) {
                    document.getElementById('resultado-copiados').textContent = resultado.copiados;
                    document.getElementById('resultado-copiados-contenedor').classList.remove('hidden');
                }

                if (resultado.codigosInvalidos > 0) {
                    const aviso = document.getElementById('resultado-aviso-codigos-invalidos');
                    const fila = resultado.codigosInvalidos === 1 ? 'fila' : 'filas';
                    const tenian = resultado.codigosInvalidos === 1 ? 'tenía' : 'tenían';
                    const creo = resultado.codigosInvalidos === 1 ? 'creó' : 'crearon';
                    aviso.textContent = `ℹ De los creados, ${resultado.codigosInvalidos} ${fila} ${tenian} un código no válido en el Excel (ej: "NaN") — se ${creo} sin código, en vez de intentar buscar una coincidencia con ese valor.`;
                    aviso.classList.remove('hidden');
                }

                if (resultado.errores.length > 0) {
                    const lista = document.getElementById('resultado-lista-errores');
                    resultado.errores.forEach(function (error) {
                        const item = document.createElement('li');
                        item.textContent = error;
                        lista.appendChild(item);
                    });
                    document.getElementById('resultado-errores-contenedor').classList.remove('hidden');
                }

                document.getElementById('panel-resultado-importacion').classList.remove('hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        })();
    </script>
@endsection
