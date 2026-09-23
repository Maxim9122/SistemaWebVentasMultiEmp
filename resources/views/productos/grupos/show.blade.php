@extends('layouts.app')

@section('titulo', $grupo->nombre)

@section('contenido')
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('productos.grupos.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Volver a Grupos</a>
        <div class="flex items-center gap-4">
            <button type="button" id="btn_exportar_grupo" class="inline-flex items-center gap-1 text-sm text-slate-600 hover:underline">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                    <path d="M12 4v10m0 0-3.5-3.5M12 14l3.5-3.5"/>
                    <path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>
                </svg>
                Descargar Excel
            </button>
            <form method="POST" action="{{ route('productos.grupos.destroy', $grupo) }}" id="form_eliminar_grupo">
                @csrf
                @method('DELETE')
                <button type="button" id="btn_eliminar_grupo" class="text-sm text-red-600 hover:underline">Eliminar grupo</button>
            </form>
        </div>
    </div>

    @include('partials.modal-confirmacion', [
        'id' => 'modal_exportar_grupo',
        'titulo' => 'Descargar Excel',
        'mensaje' => 'Se va a descargar un Excel con los '.$productos->count().' producto(s) de "'.$grupo->nombre.'".',
        'textoConfirmar' => 'Descargar',
    ])

    @include('partials.modal-confirmacion', [
        'id' => 'modal_eliminar_grupo',
        'titulo' => 'Eliminar grupo',
        'mensaje' => '¿Eliminar el grupo "'.$grupo->nombre.'"? Los productos no se borran, solo se desarma la agrupación. Esta acción no se puede deshacer.',
        'textoConfirmar' => 'Eliminar grupo',
        'claseConfirmar' => 'bg-red-600 hover:bg-red-700',
    ])

    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <form method="POST" action="{{ route('productos.grupos.ajustarPrecio', $grupo) }}" id="form_ajuste">
            @csrf
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="porcentaje" class="block text-sm font-medium mb-1">Porcentaje</label>
                    <input type="number" step="0.01" name="porcentaje" id="porcentaje" placeholder="ej: 10 o -15" required
                        class="w-32 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
                <input type="hidden" name="alcance" id="alcance_input" value="todos">
                <div id="contenedor_ids_ajuste"></div>
                <button type="submit" id="btn_aplicar_todos" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Aplicar a todo el grupo
                </button>
                <button type="submit" id="btn_aplicar_seleccionados" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                    Aplicar a los seleccionados
                </button>
                <button type="button" id="btn_crear_promo_grupo" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                    Crear promo con seleccionados
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('productos.promociones.previsualizar') }}" id="form_crear_promo_grupo" class="hidden">
            @csrf
            <div id="ids_seleccionados_promo_grupo"></div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium"></th>
                    <th class="px-4 py-2 font-medium">Nombre</th>
                    <th class="px-4 py-2 font-medium">Categoría</th>
                    <th class="px-4 py-2 font-medium">Marca</th>
                    <th class="px-4 py-2 font-medium text-right">Precio</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($productos as $producto)
                    <tr>
                        <td class="px-4 py-2">
                            <input type="checkbox" class="check-ajuste rounded border border-slate-300" value="{{ $producto->id }}">
                        </td>
                        <td class="px-4 py-2">{{ $producto->nombre }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $producto->categoria ?? '—' }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $producto->marca ?? '—' }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($producto->precio, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-slate-500">Este grupo no tiene productos.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        (function () {
            const form = document.getElementById('form_ajuste');
            const alcanceInput = document.getElementById('alcance_input');
            const porcentajeInput = document.getElementById('porcentaje');
            const contenedor = document.getElementById('contenedor_ids_ajuste');
            const totalProductos = {{ $productos->count() }};

            document.getElementById('btn_aplicar_todos').addEventListener('click', function () {
                alcanceInput.value = 'todos';
            });

            document.getElementById('btn_aplicar_seleccionados').addEventListener('click', function () {
                alcanceInput.value = 'seleccionados';
            });

            form.addEventListener('submit', function (evento) {
                const marcados = document.querySelectorAll('.check-ajuste:checked');
                const porcentaje = porcentajeInput.value;

                if (alcanceInput.value === 'seleccionados' && marcados.length === 0) {
                    evento.preventDefault();
                    alert('Tildá al menos un producto para aplicar el ajuste solo a una parte del grupo.');
                    return;
                }

                const cantidad = alcanceInput.value === 'todos' ? totalProductos : marcados.length;
                const mensaje = '¿Aplicar ' + porcentaje + '% a ' + cantidad + ' producto(s)? El precio se actualiza directo y no se puede deshacer.';

                if (!confirm(mensaje)) {
                    evento.preventDefault();
                    return;
                }

                contenedor.innerHTML = '';
                marcados.forEach(function (cb) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'productos_ids[]';
                    input.value = cb.value;
                    contenedor.appendChild(input);
                });
            });

        })();

        document.getElementById('btn_crear_promo_grupo').addEventListener('click', function () {
            const marcados = document.querySelectorAll('.check-ajuste:checked');

            if (marcados.length < 2) {
                alert('Tildá al menos 2 productos del grupo para armar una promo.');
                return;
            }

            const contenedor = document.getElementById('ids_seleccionados_promo_grupo');
            contenedor.innerHTML = '';

            marcados.forEach(function (cb) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'productos_ids[]';
                input.value = cb.value;
                contenedor.appendChild(input);
            });

            document.getElementById('form_crear_promo_grupo').submit();
        });

        function conectarModal(modalId, disparador, alConfirmar) {
            const modal = document.getElementById(modalId);
            const cancelar = document.getElementById(modalId + '_cancelar');
            const confirmar = document.getElementById(modalId + '_confirmar');

            disparador.addEventListener('click', function () {
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
                alConfirmar();
            });
        }

        conectarModal('modal_exportar_grupo', document.getElementById('btn_exportar_grupo'), function () {
            window.location.href = '{{ route('productos.grupos.exportar', $grupo) }}';
        });

        conectarModal('modal_eliminar_grupo', document.getElementById('btn_eliminar_grupo'), function () {
            document.getElementById('form_eliminar_grupo').submit();
        });
    </script>
@endsection
