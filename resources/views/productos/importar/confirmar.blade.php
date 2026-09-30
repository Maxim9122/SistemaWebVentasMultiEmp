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
    <div class="bg-white rounded-lg shadow p-6">
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

            // form.submit() (a diferencia de un <button type="submit"> normal) no
            // dispara el evento 'submit' del documento, así que el bloqueo global
            // de doble envío (partials/evitar-doble-envio) nunca se entera de este
            // form — hay que bloquear el botón acá a mano.
            document.getElementById('confirmar-modal-importar').addEventListener('click', function (evento) {
                const boton = evento.currentTarget;

                if (boton.disabled) {
                    return;
                }

                boton.disabled = true;
                boton.classList.add('opacity-60', 'cursor-not-allowed');
                boton.textContent = 'Procesando...';
                document.getElementById('cancelar-modal-importar').disabled = true;

                form.submit();
            });
        })();
    </script>
@endsection
