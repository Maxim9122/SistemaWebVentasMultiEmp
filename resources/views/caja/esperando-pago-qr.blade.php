@extends('layouts.app')

@section('titulo', 'Esperando el pago')

@php
    // El pedido puede seguir siendo un carrito (si el cajero_vendedor cobró
    // QR desde ahí) o ya estar en caja (si vino del flujo de Caja) — el
    // link de "volver" tiene que apuntar a la pantalla que de verdad sabe
    // mostrarlo, si no, 404.
    $urlVolver = $intento->pedido->esCarrito()
        ? route('carritos.show', $intento->pedido_id)
        : route('caja.show', $intento->pedido_id);
@endphp

@section('contenido')
    <div class="max-w-md mx-auto mt-8 bg-white rounded-lg shadow p-6 text-center">
        <div id="estado_pendiente">
            <div class="mx-auto mb-4 h-12 w-12 rounded-full border-4 border-slate-200 border-t-slate-900 animate-spin"></div>
            <p class="text-lg font-medium text-slate-900">Esperando el pago...</p>
            <p class="text-sm text-slate-500 mt-1">
                Monto: <strong>${{ number_format($intento->monto, 2, ',', '.') }}</strong>
            </p>
            <p class="text-sm text-slate-500 mt-3">
                Pedile al cliente que escanee el QR fijo del mostrador. Esta pantalla se actualiza sola apenas se confirme el pago.
            </p>
        </div>

        <div id="estado_expirado" class="hidden">
            <p class="text-lg font-medium text-red-700">El tiempo para pagar venció</p>
            <p class="text-sm text-slate-500 mt-1 mb-4">No se registró el pago a tiempo — podés volver e intentar con otro medio.</p>
            <a href="{{ $urlVolver }}" class="inline-block rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                Volver al cobro
            </a>
        </div>

        <div id="estado_cancelado" class="hidden">
            <p class="text-lg font-medium text-slate-700">Cobro cancelado</p>
            <a href="{{ $urlVolver }}" class="inline-block rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800 mt-3">
                Volver al cobro
            </a>
        </div>
    </div>

    <script>
        (function () {
            const urlEstado = {!! json_encode(route('caja.mercadopago.estado', $intento)) !!};
            const urlVenta = {!! json_encode(route('ventas.show', $intento->pedido_id)) !!};

            const divPendiente = document.getElementById('estado_pendiente');
            const divExpirado = document.getElementById('estado_expirado');
            const divCancelado = document.getElementById('estado_cancelado');

            function consultar() {
                fetch(urlEstado, { headers: { 'Accept': 'application/json' } })
                    .then(function (respuesta) { return respuesta.json(); })
                    .then(function (datos) {
                        if (datos.estado === 'aprobado') {
                            window.location.href = urlVenta;

                            return;
                        }

                        if (datos.estado === 'expirado') {
                            divPendiente.classList.add('hidden');
                            divExpirado.classList.remove('hidden');

                            return;
                        }

                        if (datos.estado === 'cancelado') {
                            divPendiente.classList.add('hidden');
                            divCancelado.classList.remove('hidden');

                            return;
                        }

                        setTimeout(consultar, 3000);
                    })
                    .catch(function () {
                        setTimeout(consultar, 3000);
                    });
            }

            setTimeout(consultar, 3000);
        })();
    </script>
@endsection
