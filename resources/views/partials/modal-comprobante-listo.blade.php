@php
    $esFactura = $pedidoListo->tipo_comprobante === 'factura' && $pedidoListo->factura;
    $urlDescarga = $esFactura ? route('ventas.comprobantePdf', $pedidoListo) : route('ventas.remitoPdf', $pedidoListo);
@endphp
<div id="modal_comprobante_listo" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h2 class="text-lg font-semibold mb-3">Venta registrada</h2>
        <p class="text-sm text-slate-600 mb-1">
            {{ $esFactura ? 'Factura '.$pedidoListo->factura->tipo_factura : 'Remito' }} — Venta N° {{ $pedidoListo->numero_venta }}
        </p>
        @if ($esFactura)
            <p class="text-sm text-slate-600 mb-4">CAE: {{ $pedidoListo->factura->cae }}</p>
        @endif
        <p class="text-sm text-slate-600 mb-4">¿Querés descargar el comprobante en PDF?</p>
        <div class="flex gap-2 justify-end">
            <button type="button" id="modal_comprobante_listo_cerrar" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                No, gracias
            </button>
            <a href="{{ $urlDescarga }}" class="inline-flex items-center gap-1.5 rounded bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 text-sm font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 shrink-0">
                    <path d="M12 4v10m0 0-3.5-3.5M12 14l3.5-3.5"/>
                    <path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>
                </svg>
                Descargar PDF
            </a>
        </div>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('modal_comprobante_listo');
        var cerrar = document.getElementById('modal_comprobante_listo_cerrar');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        cerrar.addEventListener('click', function () {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    })();
</script>
