<div class="border-t pt-4">
    <p class="font-medium text-slate-900 mb-1">Precios por cantidad (opcional)</p>
    <p class="text-slate-500 text-sm mb-3">
        Hasta 3 tramos de descuento por volumen. Al vender, el carrito aplica solo por la cantidad cargada —
        no hace falta elegir el precio a mano.
    </p>
    <div class="space-y-2">
        @for ($n = 1; $n <= 3; $n++)
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="cantidad_minima_{{ $n }}" class="block text-xs text-slate-500 mb-1">A partir de (unidades)</label>
                    <input id="cantidad_minima_{{ $n }}" name="cantidad_minima_{{ $n }}" type="number" step="1" min="2"
                        value="{{ old('cantidad_minima_'.$n, $producto?->{'cantidad_minima_'.$n}) }}"
                        class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label for="precio_cantidad_{{ $n }}" class="block text-xs text-slate-500 mb-1">Precio por unidad</label>
                    <input id="precio_cantidad_{{ $n }}" name="precio_cantidad_{{ $n }}" type="number" step="0.01" min="0"
                        value="{{ old('precio_cantidad_'.$n, $producto?->{'precio_cantidad_'.$n}) }}"
                        class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
            </div>
        @endfor
    </div>
</div>
