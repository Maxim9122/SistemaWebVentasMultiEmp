{{--
    Modal liviano para abrir la caja sin salir de Carritos — mismos dos
    campos que ya tiene caja-sesion.show.blade.php (monto_apertura y
    nota_apertura), mismo endpoint (caja-sesion.abrir). Para CERRAR la caja
    no hay atajo acá a propósito: hay que ir a "Mi caja", donde se ve el
    detalle de lo cobrado antes de declarar el cierre.
--}}
<div id="modal_abrir_caja" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-sm w-full p-6">
        <h2 class="text-lg font-semibold mb-3">Abrir caja</h2>

        <form method="POST" action="{{ route('caja-sesion.abrir') }}" class="space-y-3">
            @csrf

            <div>
                <label for="modal_abrir_caja_monto" class="block text-sm font-medium mb-1">Monto de apertura</label>
                <input type="number" step="0.01" min="0" name="monto_apertura" id="modal_abrir_caja_monto"
                    value="{{ old('monto_apertura') }}" required
                    class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                @error('monto_apertura')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="modal_abrir_caja_nota" class="block text-sm font-medium mb-1">Nota (opcional)</label>
                <textarea name="nota_apertura" id="modal_abrir_caja_nota" rows="2"
                    class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">{{ old('nota_apertura') }}</textarea>
            </div>

            <div class="flex gap-2 justify-end pt-2">
                <button type="button" id="modal_abrir_caja_cancelar" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </button>
                <button type="submit" class="rounded bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 text-sm font-medium">
                    Abrir caja
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var btnAbrir = document.getElementById('btn_abrir_caja_carritos');
        var modal = document.getElementById('modal_abrir_caja');
        var btnCancelar = document.getElementById('modal_abrir_caja_cancelar');

        if (!modal) return;

        function abrirModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        if (btnAbrir) {
            btnAbrir.addEventListener('click', abrirModal);
        }

        if (btnCancelar) {
            btnCancelar.addEventListener('click', function () {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        }

        @if ($errors->has('monto_apertura'))
            // Si el submit anterior volvió con error (ej. "Ya tenés una caja
            // abierta"), el modal arranca cerrado por default — sin esto el
            // mensaje quedaría invisible.
            abrirModal();
        @endif
    })();
</script>
