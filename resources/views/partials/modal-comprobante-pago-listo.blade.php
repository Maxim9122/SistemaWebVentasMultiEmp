<div id="modal_comprobante_pago_listo" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h2 class="text-lg font-semibold mb-3">Pago registrado</h2>
        <p class="text-sm text-slate-600 mb-1">
            {{ $pagoListo->cliente->nombre }} — ${{ number_format($pagoListo->total(), 2, ',', '.') }}
        </p>
        <p class="text-sm text-slate-600 mb-4">¿Querés descargar el comprobante en PDF?</p>
        <div class="flex gap-2 justify-end">
            <button type="button" id="modal_comprobante_pago_listo_cerrar" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                No, gracias
            </button>
            <a href="{{ route('creditos.pagos.comprobantePdf', $pagoListo) }}" class="rounded bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 text-sm font-medium">
                Descargar PDF
            </a>
        </div>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('modal_comprobante_pago_listo');
        var cerrar = document.getElementById('modal_comprobante_pago_listo_cerrar');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        cerrar.addEventListener('click', function () {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    })();
</script>
