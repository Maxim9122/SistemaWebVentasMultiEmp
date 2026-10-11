@extends('layouts.app')

@section('titulo', 'Mi caja')

@section('contenido')
    @if ($caja)
        <div class="max-w-xl bg-white rounded-lg shadow p-6 mb-4">
            <p class="text-sm text-slate-500 mb-1">Caja abierta desde</p>
            <p class="text-lg font-semibold mb-4">{{ $caja->abierta_at->format('d/m/Y H:i') }}</p>

            <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                <div>
                    <p class="text-slate-500">Fondo inicial</p>
                    <p class="font-medium">${{ number_format($caja->monto_apertura, 2, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Ventas en efectivo</p>
                    <p class="font-medium">${{ number_format($caja->totalVentasEfectivo(), 2, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Ventas con tarjeta</p>
                    <p class="font-medium">${{ number_format($caja->totalVentasTarjeta(), 2, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Ventas por transferencia</p>
                    <p class="font-medium">${{ number_format($caja->totalVentasTransferencia(), 2, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Pagos de créditos en efectivo</p>
                    <p class="font-medium text-emerald-600">+${{ number_format($caja->totalPagosCreditoEfectivo(), 2, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Pagos de créditos por transferencia</p>
                    <p class="font-medium text-emerald-600">+${{ number_format($caja->totalPagosCreditoTransferencia(), 2, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Egresos en efectivo</p>
                    <p class="font-medium text-red-600">-${{ number_format($caja->totalEgresosEfectivo(), 2, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-slate-500">Egresos por transferencia</p>
                    <p class="font-medium text-red-600">-${{ number_format($caja->totalEgresosTransferencia(), 2, ',', '.') }}</p>
                </div>
            </div>

            <div class="border-t pt-4 mb-4">
                <p class="text-slate-500 text-sm">Efectivo esperado en el cajón ahora mismo</p>
                <p class="text-2xl font-semibold">${{ number_format($caja->efectivoEsperado(), 2, ',', '.') }}</p>
            </div>

            <form method="POST" action="{{ route('caja-sesion.cerrar') }}" id="form_cerrar_caja" class="space-y-3 border-t pt-4">
                @csrf
                <p class="text-sm font-medium">Cerrar caja</p>
                <div>
                    <label for="monto_cierre_declarado" class="block text-sm font-medium mb-1">Efectivo contado</label>
                    <input type="number" id="monto_cierre_declarado" name="monto_cierre_declarado" step="0.01" min="0" required
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label for="nota_cierre" class="block text-sm font-medium mb-1">Nota (opcional)</label>
                    <textarea id="nota_cierre" name="nota_cierre" rows="2"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500"></textarea>
                </div>
                <button type="button" id="btn_cerrar_caja" class="rounded bg-red-600 text-white px-4 py-2 text-sm font-medium hover:bg-red-700">
                    Cerrar caja
                </button>
            </form>
        </div>

        {{-- Modal de confirmación: el resumen de arriba (fondo, ventas,
        egresos, etc.) no cambia al escribir, así que ese no hace falta
        repetirlo acá — lo que sí cambia en vivo es lo contado vs. lo
        esperado, que es justo lo que importa confirmar antes de cerrar. --}}
        <div id="modal_cerrar_caja" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-sm w-full p-6">
                <h2 class="text-lg font-semibold mb-3">Confirmar cierre de caja</h2>
                <div class="space-y-1.5 text-sm mb-4">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Efectivo esperado</span>
                        <span class="font-medium">${{ number_format($caja->efectivoEsperado(), 2, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Efectivo contado</span>
                        <span id="modal_cerrar_caja_contado" class="font-medium"></span>
                    </div>
                    <div class="flex justify-between border-t pt-1.5 mt-1.5">
                        <span class="text-slate-500">Diferencia</span>
                        <span id="modal_cerrar_caja_diferencia" class="font-semibold"></span>
                    </div>
                </div>
                <p class="text-xs text-slate-400 mb-4">
                    Esta acción no se puede deshacer — una vez cerrada, esta caja no se puede volver a usar para cobrar.
                </p>
                <div class="flex gap-2 justify-end">
                    <button type="button" id="modal_cerrar_caja_cancelar" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                        Cancelar
                    </button>
                    <button type="button" id="modal_cerrar_caja_confirmar" class="rounded bg-red-600 hover:bg-red-700 text-white px-4 py-2 text-sm font-medium">
                        Sí, cerrar caja
                    </button>
                </div>
            </div>
        </div>

        <script>
            (function () {
                var btnAbrir = document.getElementById('btn_cerrar_caja');
                var modal = document.getElementById('modal_cerrar_caja');

                if (!btnAbrir || !modal) return;

                var form = document.getElementById('form_cerrar_caja');
                var inputDeclarado = document.getElementById('monto_cierre_declarado');
                var spanContado = document.getElementById('modal_cerrar_caja_contado');
                var spanDiferencia = document.getElementById('modal_cerrar_caja_diferencia');
                var btnConfirmar = document.getElementById('modal_cerrar_caja_confirmar');
                var esperado = {{ (float) $caja->efectivoEsperado() }};

                function formatearMoneda(monto) {
                    return '$' + monto.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }

                btnAbrir.addEventListener('click', function () {
                    // Si falta el campo obligatorio, que lo marque el propio
                    // navegador en vez de abrir el modal con "$0.00" engañoso.
                    if (!inputDeclarado.reportValidity()) {
                        return;
                    }

                    var declarado = parseFloat(inputDeclarado.value) || 0;
                    var diferencia = Math.round((declarado - esperado) * 100) / 100;

                    spanContado.textContent = formatearMoneda(declarado);
                    spanDiferencia.textContent = (diferencia >= 0 ? '+' : '-') + formatearMoneda(Math.abs(diferencia));
                    spanDiferencia.className = 'font-semibold ' + (diferencia >= 0 ? 'text-emerald-600' : 'text-red-600');

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                });

                document.getElementById('modal_cerrar_caja_cancelar').addEventListener('click', function () {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                });

                btnConfirmar.addEventListener('click', function () {
                    btnConfirmar.disabled = true;
                    form.submit();
                });
            })();
        </script>

        <div class="mb-4">
            @include('partials.tabla-egresos-caja', ['caja' => $caja])
        </div>

        @include('partials.pagos-credito-caja', ['caja' => $caja])
    @else
        <div class="max-w-xl bg-white rounded-lg shadow p-6 mb-4">
            <p class="text-sm text-slate-500 mb-4">
                No tenés una caja abierta. Necesitás abrir una para poder cobrar ventas o cargar egresos.
            </p>
            <form method="POST" action="{{ route('caja-sesion.abrir') }}" class="space-y-3">
                @csrf
                <div>
                    <label for="monto_apertura" class="block text-sm font-medium mb-1">Fondo inicial en efectivo (para vuelto)</label>
                    <input type="number" id="monto_apertura" name="monto_apertura" step="0.01" min="0" required
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label for="nota_apertura" class="block text-sm font-medium mb-1">Nota (opcional)</label>
                    <textarea id="nota_apertura" name="nota_apertura" rows="2"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500"></textarea>
                </div>
                <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Abrir caja
                </button>
            </form>
        </div>

        @if ($ultimasCerradas->isNotEmpty())
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <p class="px-4 py-2 text-sm font-medium border-b bg-slate-50">Últimas cajas cerradas</p>
                <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 text-left">
                        <tr>
                            <th class="px-4 py-2 font-medium">Abierta</th>
                            <th class="px-4 py-2 font-medium">Cerrada</th>
                            <th class="px-4 py-2 font-medium text-right">Fondo</th>
                            <th class="px-4 py-2 font-medium text-right">Declarado</th>
                            <th class="px-4 py-2 font-medium text-right">Diferencia</th>
                            <th class="px-4 py-2 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($ultimasCerradas as $c)
                            <tr>
                                <td class="px-4 py-2 text-slate-500">{{ $c->abierta_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2 text-slate-500">{{ $c->cerrada_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format($c->monto_apertura, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format($c->monto_cierre_declarado, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-right font-medium @class(['text-emerald-600' => $c->diferencia() >= 0, 'text-red-600' => $c->diferencia() < 0])">
                                    {{ $c->diferencia() >= 0 ? '+' : '-' }}${{ number_format(abs($c->diferencia()), 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <a href="{{ route('caja-sesion.detalle', $c) }}" class="text-sm text-slate-600 hover:underline">Ver detalle</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        @endif
    @endif
@endsection
