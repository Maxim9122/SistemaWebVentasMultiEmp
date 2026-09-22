@php
    $idUnico = uniqid('pagoscred_');
    $pagosCreditoCaja = $caja->pagosCredito()->with(['cliente', 'usuario'])->latest()->get();
@endphp

<div class="bg-white rounded-lg shadow p-5">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-base font-semibold">Pagos de créditos recibidos en esta caja</h2>
        <button type="button" id="{{ $idUnico }}_btn_abrir" class="text-sm text-slate-600 hover:underline">
            Ver detalle ({{ $pagosCreditoCaja->count() }})
        </button>
    </div>

    <div class="grid grid-cols-3 gap-4 text-sm">
        <div>
            <p class="text-slate-500">Efectivo</p>
            <p class="font-medium">${{ number_format($caja->totalPagosCreditoEfectivo(), 2, ',', '.') }}</p>
        </div>
        <div>
            <p class="text-slate-500">Tarjeta</p>
            <p class="font-medium">${{ number_format($caja->totalPagosCreditoTarjeta(), 2, ',', '.') }}</p>
        </div>
        <div>
            <p class="text-slate-500">Transferencia</p>
            <p class="font-medium">${{ number_format($caja->totalPagosCreditoTransferencia(), 2, ',', '.') }}</p>
        </div>
    </div>
</div>

<div id="{{ $idUnico }}_modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-6 max-h-[85vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">Pagos de créditos recibidos en esta caja</h2>
            <button type="button" id="{{ $idUnico }}_btn_cerrar" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-3 py-2 font-medium">Cliente</th>
                    <th class="px-3 py-2 font-medium">Fecha y hora</th>
                    <th class="px-3 py-2 font-medium">Registrado por</th>
                    <th class="px-3 py-2 font-medium text-right">Efectivo</th>
                    <th class="px-3 py-2 font-medium text-right">Tarjeta</th>
                    <th class="px-3 py-2 font-medium text-right">Transferencia</th>
                    <th class="px-3 py-2 font-medium text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($pagosCreditoCaja as $pago)
                    <tr>
                        <td class="px-3 py-2">{{ $pago->cliente->nombre }}</td>
                        <td class="px-3 py-2 text-slate-500">{{ $pago->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-3 py-2 text-slate-500">{{ $pago->usuario->name }}</td>
                        <td class="px-3 py-2 text-right">{{ $pago->monto_efectivo > 0 ? '$'.number_format($pago->monto_efectivo, 2, ',', '.') : '—' }}</td>
                        <td class="px-3 py-2 text-right">{{ $pago->monto_tarjeta > 0 ? '$'.number_format($pago->monto_tarjeta, 2, ',', '.') : '—' }}</td>
                        <td class="px-3 py-2 text-right">{{ $pago->monto_transferencia > 0 ? '$'.number_format($pago->monto_transferencia, 2, ',', '.') : '—' }}</td>
                        <td class="px-3 py-2 text-right font-medium">${{ number_format($pago->total(), 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-6 text-center text-slate-500">No se recibió ningún pago de crédito en esta caja.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    (function () {
        const btnAbrir = document.getElementById({{ Js::from($idUnico.'_btn_abrir') }});
        const btnCerrar = document.getElementById({{ Js::from($idUnico.'_btn_cerrar') }});
        const modal = document.getElementById({{ Js::from($idUnico.'_modal') }});

        btnAbrir.addEventListener('click', function () {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });

        function cerrar() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        btnCerrar.addEventListener('click', cerrar);
        modal.addEventListener('click', function (evento) {
            if (evento.target === modal) cerrar();
        });
    })();
</script>
