@extends('layouts.app')

@section('titulo', 'Editar promoción')

@section('contenido')
    <div class="max-w-2xl bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('productos.promociones.update', $promocion) }}" id="form_editar_promo" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="nombre" class="block text-sm font-medium mb-1">Nombre de la promoción</label>
                <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $promocion->nombre) }}" required
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-left">
                        <tr>
                            <th class="px-3 py-2 font-medium">Producto</th>
                            <th class="px-3 py-2 font-medium text-right">Precio catálogo</th>
                            <th class="px-3 py-2 font-medium">Cantidad</th>
                            <th class="px-3 py-2 font-medium text-right">Subtotal</th>
                            <th class="px-3 py-2 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($items as $item)
                            <tr class="fila-item">
                                <td class="px-3 py-2">{{ $item->producto->nombre }}</td>
                                <td class="px-3 py-2 text-right">${{ number_format($item->producto->precio, 2, ',', '.') }}</td>
                                <td class="px-3 py-2">
                                    <input type="number" name="cantidades[{{ $item->id }}]" value="{{ old('cantidades.'.$item->id, $item->cantidad) }}" min="1"
                                        class="cantidad-item w-20 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
                                        data-precio="{{ $item->producto->precio }}">
                                </td>
                                <td class="px-3 py-2 text-right subtotal-item">${{ number_format($item->producto->precio * $item->cantidad, 2, ',', '.') }}</td>
                                <td class="px-3 py-2">
                                    <label class="flex items-center gap-1 text-xs text-red-600">
                                        <input type="checkbox" name="eliminar[]" value="{{ $item->id }}" class="check-eliminar rounded border border-slate-300">
                                        Quitar
                                    </label>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t">
                            <td colspan="3" class="px-3 py-2 text-right font-medium">Total catálogo</td>
                            <td class="px-3 py-2 text-right font-medium" id="total_catalogo">$0,00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                <p class="text-xs text-slate-400 mt-1">Una promoción necesita al menos 2 productos — no podés quitar tantos que queden menos de 2.</p>
            </div>

            <div>
                <label for="precio_final" class="block text-sm font-medium mb-1">Precio final de la promoción</label>
                <input id="precio_final" name="precio_final" type="number" step="0.01" min="0" value="{{ old('precio_final', $promocion->precio) }}" required
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
            </div>

            <div class="flex gap-2">
                <button type="button" id="btn_abrir_confirmar" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Guardar cambios
                </button>
                <a href="{{ route('productos.index') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    @include('partials.modal-confirmacion', [
        'id' => 'modal_confirmar_promo',
        'titulo' => 'Confirmar cambios',
        'mensaje' => 'Revisá los datos antes de confirmar.',
        'textoConfirmar' => 'Guardar cambios',
    ])

    <script>
        (function () {
            const totalCatalogo = document.getElementById('total_catalogo');

            function formatearPrecio(valor) {
                return valor.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function recalcular() {
                let total = 0;
                let restantes = 0;

                document.querySelectorAll('.fila-item').forEach(function (fila) {
                    const eliminar = fila.querySelector('.check-eliminar').checked;
                    const input = fila.querySelector('.cantidad-item');

                    fila.classList.toggle('opacity-40', eliminar);
                    input.disabled = eliminar;

                    if (eliminar) {
                        fila.querySelector('.subtotal-item').textContent = '—';
                        return;
                    }

                    restantes++;
                    const precio = parseFloat(input.dataset.precio) || 0;
                    const cantidad = parseInt(input.value, 10) || 0;
                    const subtotal = precio * cantidad;
                    total += subtotal;

                    fila.querySelector('.subtotal-item').textContent = '$' + formatearPrecio(subtotal);
                });

                totalCatalogo.textContent = '$' + formatearPrecio(total);

                return restantes;
            }

            document.querySelectorAll('.cantidad-item').forEach(function (input) {
                input.addEventListener('input', recalcular);
            });

            document.querySelectorAll('.check-eliminar').forEach(function (checkbox) {
                checkbox.addEventListener('change', recalcular);
            });

            recalcular();

            const form = document.getElementById('form_editar_promo');
            const modal = document.getElementById('modal_confirmar_promo');
            const mensaje = document.getElementById('modal_confirmar_promo_mensaje');
            const cancelar = document.getElementById('modal_confirmar_promo_cancelar');
            const confirmar = document.getElementById('modal_confirmar_promo_confirmar');

            document.getElementById('btn_abrir_confirmar').addEventListener('click', function () {
                const nombre = document.getElementById('nombre').value.trim();
                const precioFinal = document.getElementById('precio_final').value;
                const restantes = recalcular();

                if (! nombre) {
                    alert('Ingresá un nombre para la promoción.');
                    return;
                }

                if (precioFinal === '' || parseFloat(precioFinal) < 0) {
                    alert('Ingresá el precio final de la promoción.');
                    return;
                }

                if (restantes < 2) {
                    alert('Una promoción necesita al menos 2 productos.');
                    return;
                }

                mensaje.textContent = '¿Guardar "' + nombre + '" con ' + restantes + ' producto(s), a $' + formatearPrecio(parseFloat(precioFinal)) + '?';
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
                form.submit();
            });
        })();
    </script>
@endsection
