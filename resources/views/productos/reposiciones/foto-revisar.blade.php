@extends('layouts.app')

@section('titulo', 'Revisar reposición detectada')

@section('contenido')
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm text-slate-600 mb-1">
            Esto es lo que se pudo leer de la foto. Para cada línea, confirmá o corregí el producto del catálogo al
            que corresponde y la cantidad — solo se auto-completó cuando hubo una coincidencia exacta de código o
            nombre, cualquier otra línea queda para que la busques a mano. Nada se guarda todavía.
        </p>
        <p class="text-xs text-slate-400 mb-4">
            También podés aplicar un aumento o una baja de precio (en %) a todos los productos de la lista, o solo a
            los que tildes, junto con confirmar la reposición.
        </p>

        <div class="overflow-x-auto -mx-6 px-6">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-slate-500 border-b">
                        <th class="px-2 py-2 font-medium whitespace-nowrap">Leído en la foto</th>
                        <th class="px-2 py-2 font-medium whitespace-nowrap">Producto en el catálogo</th>
                        <th class="px-2 py-2 font-medium whitespace-nowrap">Cantidad</th>
                        <th class="px-2 py-2 font-medium whitespace-nowrap">
                            Ajustar precio
                        </th>
                        <th class="px-2 py-2"></th>
                    </tr>
                </thead>
                <tbody id="filas_reposicion" class="divide-y">
                    @foreach ($filas as $fila)
                        <tr class="align-top">
                            <td class="px-2 py-2 text-xs text-slate-500 max-w-[10rem]">
                                {{ $fila['nombre_leido'] ?: '—' }}
                                @if ($fila['codigo_leido'])
                                    <br><span class="text-slate-400">Cód: {{ $fila['codigo_leido'] }}</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 relative">
                                <input type="text" class="buscador-producto w-56 rounded border border-slate-300 text-sm px-2 py-1 focus:border-slate-500 focus:ring-slate-500"
                                    autocomplete="off" placeholder="Buscar producto (nombre o código)..."
                                    value="{{ $fila['producto_nombre'] ? $fila['producto_nombre'].($fila['producto_codigo'] ? ' ('.$fila['producto_codigo'].')' : '') : '' }}">
                                <input type="hidden" class="producto-id-oculto" value="{{ $fila['producto_id'] }}">
                                <ul class="resultados-producto hidden absolute z-10 mt-1 w-56 max-h-48 overflow-auto rounded border border-slate-200 bg-white shadow-lg"></ul>
                                <p class="estado-fila text-xs mt-1 {{ $fila['producto_id'] ? 'text-slate-400' : 'text-amber-600' }}">
                                    {{ $fila['producto_id'] ? 'Stock actual: '.$fila['producto_stock'] : 'Sin coincidencia exacta — buscalo arriba.' }}
                                </p>
                            </td>
                            <td class="px-2 py-2">
                                <input type="number" min="1" class="cantidad-input w-24 rounded border border-slate-300 text-sm px-2 py-1 focus:border-slate-500 focus:ring-slate-500"
                                    value="{{ $fila['cantidad'] }}">
                            </td>
                            <td class="px-2 py-2 text-center">
                                <input type="checkbox" class="ajustar-precio-check w-4 h-4">
                            </td>
                            <td class="px-2 py-2">
                                <button type="button" class="btn-quitar-fila text-xs text-red-600 hover:underline whitespace-nowrap">Quitar</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <button type="button" id="btn_agregar_fila" class="mt-3 text-sm text-slate-600 hover:underline">
            + Agregar fila
        </button>

        <form method="POST" action="{{ route('productos.reposiciones.store') }}" id="form_confirmar_reposicion" class="mt-6 pt-4 border-t space-y-4">
            @csrf
            <input type="hidden" name="proveedor_id" value="{{ $proveedorId }}">
            <div id="inputs_ocultos"></div>

            <div class="rounded border border-slate-200 bg-slate-50 p-4 space-y-2">
                <div class="flex items-center justify-between">
                    <label for="porcentaje" class="block text-sm font-medium">Ajuste de precio (opcional)</label>
                    <div class="flex gap-3 text-xs">
                        <button type="button" id="btn_marcar_todos" class="text-slate-600 hover:underline">Marcar todos</button>
                        <button type="button" id="btn_desmarcar_todos" class="text-slate-600 hover:underline">Desmarcar todos</button>
                    </div>
                </div>
                <input type="number" step="0.01" id="porcentaje" placeholder="Ej: 10 para +10%, -5 para -5%"
                    class="w-48 rounded border border-slate-300 text-sm px-2 py-1 focus:border-slate-500 focus:ring-slate-500">
                <p class="text-xs text-slate-500">
                    Se aplica solo a las líneas con el casillero "Ajustar precio" tildado. Si no cargás un porcentaje,
                    no se toca ningún precio.
                </p>
                @error('porcentaje')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            @error('reposicion')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="flex gap-2">
                <button type="submit" id="btn_confirmar" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Confirmar reposición
                </button>
                <a href="{{ route('productos.reposiciones.importarFoto.subir') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Volver a sacar la foto
                </a>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const urlBuscar = {{ Js::from($urlBuscar) }};
            const tbody = document.getElementById('filas_reposicion');
            const btnAgregar = document.getElementById('btn_agregar_fila');
            const form = document.getElementById('form_confirmar_reposicion');
            const inputsOcultos = document.getElementById('inputs_ocultos');
            const inputPorcentaje = document.getElementById('porcentaje');
            const btnMarcarTodos = document.getElementById('btn_marcar_todos');
            const btnDesmarcarTodos = document.getElementById('btn_desmarcar_todos');
            const btnConfirmar = document.getElementById('btn_confirmar');

            let idPeticion = 0;

            function filaVacia() {
                const fila = document.createElement('tr');
                fila.className = 'align-top';
                fila.innerHTML = `
                    <td class="px-2 py-2 text-xs text-slate-500 max-w-[10rem]">— (agregada a mano)</td>
                    <td class="px-2 py-2 relative">
                        <input type="text" class="buscador-producto w-56 rounded border border-slate-300 text-sm px-2 py-1 focus:border-slate-500 focus:ring-slate-500"
                            autocomplete="off" placeholder="Buscar producto (nombre o código)...">
                        <input type="hidden" class="producto-id-oculto" value="">
                        <ul class="resultados-producto hidden absolute z-10 mt-1 w-56 max-h-48 overflow-auto rounded border border-slate-200 bg-white shadow-lg"></ul>
                        <p class="estado-fila text-xs mt-1 text-amber-600">Buscá el producto arriba.</p>
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" min="1" class="cantidad-input w-24 rounded border border-slate-300 text-sm px-2 py-1 focus:border-slate-500 focus:ring-slate-500">
                    </td>
                    <td class="px-2 py-2 text-center">
                        <input type="checkbox" class="ajustar-precio-check w-4 h-4">
                    </td>
                    <td class="px-2 py-2">
                        <button type="button" class="btn-quitar-fila text-xs text-red-600 hover:underline whitespace-nowrap">Quitar</button>
                    </td>
                `;

                return fila;
            }

            btnAgregar.addEventListener('click', function () {
                tbody.appendChild(filaVacia());
            });

            tbody.addEventListener('click', function (evento) {
                if (evento.target.classList.contains('btn-quitar-fila')) {
                    evento.target.closest('tr').remove();
                }
            });

            btnMarcarTodos.addEventListener('click', function () {
                tbody.querySelectorAll('.ajustar-precio-check').forEach(function (check) { check.checked = true; });
            });

            btnDesmarcarTodos.addEventListener('click', function () {
                tbody.querySelectorAll('.ajustar-precio-check').forEach(function (check) { check.checked = false; });
            });

            // Búsqueda por fila, con debounce — mismo criterio que el
            // buscador del modal "Reponer stock" en productos/index.
            function buscarEnServidor(texto) {
                const miId = ++idPeticion;

                return fetch(urlBuscar + '?q=' + encodeURIComponent(texto), {
                    headers: { 'Accept': 'application/json' },
                })
                    .then((respuesta) => respuesta.json())
                    .then((datos) => (miId === idPeticion ? datos : null))
                    .catch(() => null);
            }

            function seleccionarProducto(fila, producto) {
                fila.querySelector('.buscador-producto').value = producto.nombre + (producto.codigo ? ' (' + producto.codigo + ')' : '');
                fila.querySelector('.producto-id-oculto').value = producto.id;
                const estado = fila.querySelector('.estado-fila');
                estado.textContent = 'Stock actual: ' + (producto.stock !== null && producto.stock !== undefined ? producto.stock : '—');
                estado.className = 'estado-fila text-xs mt-1 text-slate-400';
                fila.querySelector('.resultados-producto').innerHTML = '';
                fila.querySelector('.resultados-producto').classList.add('hidden');
            }

            let timerDebounce = null;

            tbody.addEventListener('input', function (evento) {
                if (!evento.target.classList.contains('buscador-producto')) return;

                const fila = evento.target.closest('tr');
                const texto = evento.target.value.trim();
                fila.querySelector('.producto-id-oculto').value = '';

                const estado = fila.querySelector('.estado-fila');
                estado.textContent = 'Sin coincidencia exacta — buscalo arriba.';
                estado.className = 'estado-fila text-xs mt-1 text-amber-600';

                clearTimeout(timerDebounce);

                if (texto === '') {
                    fila.querySelector('.resultados-producto').innerHTML = '';
                    fila.querySelector('.resultados-producto').classList.add('hidden');
                    return;
                }

                timerDebounce = setTimeout(function () {
                    buscarEnServidor(texto).then(function (resultados) {
                        if (resultados === null) return;

                        const lista = fila.querySelector('.resultados-producto');
                        lista.innerHTML = '';

                        if (resultados.length === 0) {
                            lista.classList.add('hidden');
                            return;
                        }

                        resultados.forEach(function (producto) {
                            const li = document.createElement('li');
                            li.textContent = producto.nombre + (producto.codigo ? ' (' + producto.codigo + ')' : '') + ' — stock: ' + producto.stock;
                            li.className = 'px-3 py-2 text-sm cursor-pointer hover:bg-slate-100';
                            li.addEventListener('mousedown', function (ev) {
                                ev.preventDefault();
                                seleccionarProducto(fila, producto);
                            });
                            lista.appendChild(li);
                        });

                        lista.classList.remove('hidden');
                    });
                }, 200);
            });

            tbody.addEventListener('blur', function (evento) {
                if (!evento.target.classList.contains('buscador-producto')) return;

                const fila = evento.target.closest('tr');
                setTimeout(function () {
                    fila.querySelector('.resultados-producto').classList.add('hidden');
                }, 100);
            }, true);

            form.addEventListener('submit', function (evento) {
                inputsOcultos.innerHTML = '';

                let indice = 0;
                let filaInvalida = false;
                const idsAjustar = [];

                Array.from(tbody.querySelectorAll('tr')).forEach(function (fila) {
                    const productoId = fila.querySelector('.producto-id-oculto').value;
                    const cantidad = parseInt(fila.querySelector('.cantidad-input').value, 10);
                    const marcada = fila.querySelector('.ajustar-precio-check').checked;

                    if (!productoId && !(cantidad > 0)) return; // fila vacía, se ignora

                    if (!productoId || !(cantidad > 0)) {
                        filaInvalida = true;
                        return;
                    }

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

                    if (marcada) idsAjustar.push(productoId);

                    indice++;
                });

                if (filaInvalida) {
                    evento.preventDefault();
                    alert('Hay filas con cantidad pero sin producto asociado (o sin cantidad cargada) — completalas o quitalas antes de continuar.');
                    return;
                }

                if (indice === 0) {
                    evento.preventDefault();
                    alert('No hay ningún producto para reponer.');
                    return;
                }

                const porcentaje = inputPorcentaje.value.trim();

                if (porcentaje !== '') {
                    const inputPorcentajeOculto = document.createElement('input');
                    inputPorcentajeOculto.type = 'hidden';
                    inputPorcentajeOculto.name = 'porcentaje';
                    inputPorcentajeOculto.value = porcentaje;
                    inputsOcultos.appendChild(inputPorcentajeOculto);

                    idsAjustar.forEach(function (id) {
                        const inputAjustarId = document.createElement('input');
                        inputAjustarId.type = 'hidden';
                        inputAjustarId.name = 'ajustar_precio_ids[]';
                        inputAjustarId.value = id;
                        inputsOcultos.appendChild(inputAjustarId);
                    });
                }

                btnConfirmar.disabled = true;
                btnConfirmar.textContent = 'Confirmando...';
            });
        })();
    </script>
@endsection
