{{--
    Modal de "cobro con QR de Mercado Pago" — intercepta el submit del form
    cuyo id se pasa en $formId cuando el medio elegido es mercadopago (lo
    detecta mirando el hidden monto_mercadopago que arma formulario-cobro.blade.php),
    manda el cobro por fetch en vez de un submit normal, y muestra acá mismo
    la espera/resultado en vez de navegar a otra pantalla.
--}}
@php $formId = $formId ?? 'form_cobro'; @endphp

<div id="modal_cobro_qr" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 text-center">
        <div id="modal_cobro_qr_pendiente">
            <div class="mx-auto mb-4 h-12 w-12 rounded-full border-4 border-slate-200 border-t-slate-900 animate-spin"></div>
            <p class="text-lg font-medium text-slate-900">Esperando el pago...</p>
            <p id="modal_cobro_qr_monto" class="text-sm text-slate-500 mt-1"></p>
            <p class="text-sm text-slate-500 mt-3">
                Pedile al cliente que escanee el QR fijo del mostrador. Esto se actualiza solo apenas se confirme el pago.
            </p>
        </div>

        <div id="modal_cobro_qr_expirado" class="hidden">
            <p class="text-lg font-medium text-red-700">El tiempo para pagar venció</p>
            <p class="text-sm text-slate-500 mt-1 mb-4">No se registró el pago a tiempo — podés cerrar e intentar con otro medio.</p>
            <button type="button" id="modal_cobro_qr_cerrar_expirado" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                Cerrar
            </button>
        </div>

        <div id="modal_cobro_qr_cancelado" class="hidden">
            <p class="text-lg font-medium text-slate-700">Cobro cancelado</p>
            <button type="button" id="modal_cobro_qr_cerrar_cancelado" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800 mt-3">
                Cerrar
            </button>
        </div>

        <div id="modal_cobro_qr_error" class="hidden">
            <p class="text-lg font-medium text-red-700">No se pudo iniciar el cobro</p>
            <p id="modal_cobro_qr_error_msg" class="text-sm text-slate-500 mt-1 mb-4"></p>
            <button type="button" id="modal_cobro_qr_cerrar_error" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        const form = document.getElementById({{ Js::from($formId) }});

        if (! form) return;

        const modal = document.getElementById('modal_cobro_qr');
        const divPendiente = document.getElementById('modal_cobro_qr_pendiente');
        const divExpirado = document.getElementById('modal_cobro_qr_expirado');
        const divCancelado = document.getElementById('modal_cobro_qr_cancelado');
        const divError = document.getElementById('modal_cobro_qr_error');
        const montoTexto = document.getElementById('modal_cobro_qr_monto');
        const errorTexto = document.getElementById('modal_cobro_qr_error_msg');
        const estados = [divPendiente, divExpirado, divCancelado, divError];

        function mostrarEstado(div) {
            estados.forEach((d) => d.classList.add('hidden'));
            div.classList.remove('hidden');
        }

        function abrirModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function cerrarModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.getElementById('modal_cobro_qr_cerrar_expirado').addEventListener('click', cerrarModal);
        document.getElementById('modal_cobro_qr_cerrar_cancelado').addEventListener('click', cerrarModal);
        document.getElementById('modal_cobro_qr_cerrar_error').addEventListener('click', cerrarModal);

        function formatearMoneda(monto) {
            return '$' + monto.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function consultar(estadoUrl, redirectUrl) {
            fetch(estadoUrl, { headers: { 'Accept': 'application/json' } })
                .then((respuesta) => respuesta.json())
                .then((datos) => {
                    if (datos.estado === 'aprobado') {
                        window.location.href = redirectUrl;

                        return;
                    }

                    if (datos.estado === 'expirado') {
                        mostrarEstado(divExpirado);

                        return;
                    }

                    if (datos.estado === 'cancelado') {
                        mostrarEstado(divCancelado);

                        return;
                    }

                    setTimeout(function () { consultar(estadoUrl, redirectUrl); }, 3000);
                })
                .catch(function () {
                    setTimeout(function () { consultar(estadoUrl, redirectUrl); }, 3000);
                });
        }

        form.addEventListener('submit', function (evento) {
            // En Carrito, el form tiene además un select "destino" — si no es
            // "inmediato" (ej. "para otro día"), el cobro ni se procesa en el
            // servidor, así que un monto_mercadopago que haya quedado cargado
            // de antes no debe interceptar ese submit.
            const destinoEl = document.getElementById('destino');
            if (destinoEl && destinoEl.value !== 'inmediato') {
                return;
            }

            const hiddenMp = form.querySelector('input[name="monto_mercadopago"]');
            const monto = hiddenMp ? (parseFloat(hiddenMp.value) || 0) : 0;

            if (monto <= 0) {
                return;
            }

            evento.preventDefault();

            const boton = form.querySelector('button[type="submit"]');
            if (boton) boton.disabled = true;

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'Accept': 'application/json' },
            })
                .then(async function (respuesta) {
                    const datos = await respuesta.json().catch(function () { return {}; });

                    if (! respuesta.ok) {
                        errorTexto.textContent = datos.message || 'Ocurrió un error al iniciar el cobro.';
                        mostrarEstado(divError);
                        abrirModal();
                        if (boton) boton.disabled = false;

                        return;
                    }

                    montoTexto.innerHTML = 'Monto: <strong>' + formatearMoneda(datos.monto) + '</strong>';
                    mostrarEstado(divPendiente);
                    abrirModal();
                    setTimeout(function () { consultar(datos.estado_url, datos.redirect_url); }, 3000);
                })
                .catch(function () {
                    errorTexto.textContent = 'No se pudo conectar con el servidor.';
                    mostrarEstado(divError);
                    abrirModal();
                    if (boton) boton.disabled = false;
                });
        });
    })();
</script>
