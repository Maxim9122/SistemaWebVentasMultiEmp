@extends('layouts.app')

@section('titulo', 'Crear promoción')

@section('contenido')
    <div class="max-w-2xl bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('productos.promociones.store') }}" id="form_crear_promo" class="space-y-4">
            @csrf

            <div>
                <label for="nombre" class="block text-sm font-medium mb-1">Nombre de la promoción</label>
                <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" required
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
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($productos as $producto)
                            <tr>
                                <td class="px-3 py-2">{{ $producto->nombre }}</td>
                                <td class="px-3 py-2 text-right">${{ number_format($producto->precio, 2, ',', '.') }}</td>
                                <td class="px-3 py-2">
                                    <input type="hidden" name="productos_ids[]" value="{{ $producto->id }}">
                                    <input type="number" name="cantidades[]" value="1" min="1"
                                        class="cantidad-item w-20 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
                                        data-precio="{{ $producto->precio }}">
                                </td>
                                <td class="px-3 py-2 text-right subtotal-item">${{ number_format($producto->precio, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t">
                            <td colspan="3" class="px-3 py-2 text-right font-medium">Total catálogo</td>
                            <td class="px-3 py-2 text-right font-medium" id="total_catalogo">$0,00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div>
                <label for="precio_final" class="block text-sm font-medium mb-1">Precio final de la promoción</label>
                <input id="precio_final" name="precio_final" type="number" step="0.01" min="0" value="{{ old('precio_final') }}" required
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                <p class="text-xs text-slate-400 mt-1">Es lo que se cobra al vender la promo. No tiene que coincidir con el total catálogo de arriba.</p>
            </div>

            <div class="flex gap-2">
                <button type="button" id="btn_abrir_confirmar" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Crear promoción
                </button>
                <a href="{{ route('productos.index') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    @include('partials.modal-confirmacion', [
        'id' => 'modal_confirmar_promo',
        'titulo' => 'Confirmar promoción',
        'mensaje' => 'Revisá los datos antes de confirmar.',
        'textoConfirmar' => 'Crear promoción',
    ])

    <script>
        (function () {
            const filas = document.querySelectorAll('.cantidad-item');
            const totalCatalogo = document.getElementById('total_catalogo');

            function formatearPrecio(valor) {
                return valor.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function recalcular() {
                let total = 0;

                filas.forEach(function (input) {
                    const precio = parseFloat(input.dataset.precio) || 0;
                    const cantidad = parseInt(input.value, 10) || 0;
                    const subtotal = precio * cantidad;
                    total += subtotal;

                    input.closest('tr').querySelector('.subtotal-item').textContent = '$' + formatearPrecio(subtotal);
                });

                totalCatalogo.textContent = '$' + formatearPrecio(total);
            }

            filas.forEach(function (input) {
                input.addEventListener('input', recalcular);
            });

            recalcular();

            const form = document.getElementById('form_crear_promo');
            const modal = document.getElementById('modal_confirmar_promo');
            const mensaje = document.getElementById('modal_confirmar_promo_mensaje');
            const cancelar = document.getElementById('modal_confirmar_promo_cancelar');
            const confirmar = document.getElementById('modal_confirmar_promo_confirmar');

            document.getElementById('btn_abrir_confirmar').addEventListener('click', function () {
                const nombre = document.getElementById('nombre').value.trim();
                const precioFinal = document.getElementById('precio_final').value;

                if (! nombre) {
                    alert('Ingresá un nombre para la promoción.');
                    return;
                }

                if (precioFinal === '' || parseFloat(precioFinal) < 0) {
                    alert('Ingresá el precio final de la promoción.');
                    return;
                }

                mensaje.textContent = '¿Crear la promoción "' + nombre + '" con ' + filas.length + ' producto(s), a $' + formatearPrecio(parseFloat(precioFinal)) + '?';
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
