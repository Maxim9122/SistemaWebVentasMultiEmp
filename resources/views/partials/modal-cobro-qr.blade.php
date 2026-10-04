{{--
    Modal de "cobro con QR de Mercado Pago" — intercepta el submit del form
    cuyo id se pasa en $formId cuando el medio elegido es mercadopago (lo
    detecta mirando el hidden monto_mercadopago que arma formulario-cobro.blade.php),
    manda el cobro por fetch en vez de un submit normal, y muestra acá mismo
    la espera/resultado en vez de navegar a otra pantalla. El pedido/carrito
    nunca se toca acá — cancelar o dejar vencer el cobro QR lo deja intacto
    para reintentar con otro medio desde el mismo form.
--}}
@php
    $formId = $formId ?? 'form_cobro';
    $intentoQrPendiente = $intentoQrPendiente ?? null;
@endphp

<div id="modal_cobro_qr" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 text-center">
        <div id="modal_cobro_qr_pendiente">
            <div class="mx-auto mb-4 h-12 w-12 rounded-full border-4 border-slate-200 border-t-slate-900 animate-spin"></div>
            <p class="text-lg font-medium text-slate-900">Esperando el pago...</p>
            <p id="modal_cobro_qr_monto" class="text-sm text-slate-500 mt-1"></p>
            <p class="text-sm text-slate-500 mt-3 mb-4">
                Pedile al cliente que escanee el QR fijo del mostrador. Esto se actualiza solo apenas se confirme el pago.
            </p>
            <button type="button" id="modal_cobro_qr_cancelar" class="text-sm text-red-600 hover:underline">
                Cancelar este cobro
            </button>
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
            <p class="text-sm text-slate-500 mt-1 mb-4">El carrito sigue igual — podés reintentar con otro medio.</p>
            <button type="button" id="modal_cobro_qr_cerrar_cancelado" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
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

        const redirectUrlDefault = {{ Js::from($redirectUrl ?? '') }};
        const intentoPendiente = {{ Js::from($intentoQrPendiente ? [
            'monto' => (float) $intentoQrPendiente->monto,
            'estado_url' => route('caja.mercadopago.estado', $intentoQrPendiente),
            'cancelar_url' => route('caja.mercadopago.cancelar', $intentoQrPendiente),
        ] : null) }};

        const modal = document.getElementById('modal_cobro_qr');
        const divPendiente = document.getElementById('modal_cobro_qr_pendiente');
        const divExpirado = document.getElementById('modal_cobro_qr_expirado');
        const divCancelado = document.getElementById('modal_cobro_qr_cancelado');
        const divError = document.getElementById('modal_cobro_qr_error');
        const montoTexto = document.getElementById('modal_cobro_qr_monto');
        const errorTexto = document.getElementById('modal_cobro_qr_error_msg');
        const botonCancelar = document.getElementById('modal_cobro_qr_cancelar');
        const estados = [divPendiente, divExpirado, divCancelado, divError];

        // "enviando" cubre desde que se apreta "Confirmar" hasta que el
        // intento queda realmente cerrado (aprobado/cancelado/vencido/error)
        // — el botón de submit del form queda deshabilitado todo ese tiempo,
        // así un doble click no dispara dos cobros QR en paralelo.
        let enviando = false;
        let siguePolling = true;
        let cancelarUrl = null;

        function habilitarBoton() {
            enviando = false;

            const boton = form.querySelector('button[type="submit"]');
            if (boton) boton.disabled = false;
        }

        function mostrarEstado(div) {
            estados.forEach((d) => d.classList.add('hidden'));
            div.classList.remove('hidden');
        }

        function abrirModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function cerrarModalYHabilitar() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            siguePolling = false;
            habilitarBoton();
        }

        document.getElementById('modal_cobro_qr_cerrar_expirado').addEventListener('click', cerrarModalYHabilitar);
        document.getElementById('modal_cobro_qr_cerrar_cancelado').addEventListener('click', cerrarModalYHabilitar);
        document.getElementById('modal_cobro_qr_cerrar_error').addEventListener('click', cerrarModalYHabilitar);

        botonCancelar.addEventListener('click', function () {
            if (! cancelarUrl || ! confirm('¿Cancelar este cobro con QR? El carrito no se pierde, vas a poder cobrar con otro medio.')) {
                return;
            }

            botonCancelar.disabled = true;
            botonCancelar.textContent = 'Cancelando...';

            const token = form.querySelector('input[name="_token"]').value;

            fetch(cancelarUrl, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            })
                .then(async function (respuesta) {
                    const datos = await respuesta.json().catch(function () { return {}; });

                    if (! respuesta.ok) {
                        // Lo más probable: Mercado Pago ya proceso el pago
                        // justo cuando se apretó cancelar — se deja que el
                        // polling (que sigue corriendo) resuelva el estado
                        // real en vez de forzar nada acá.
                        alert(datos.message || 'No se pudo cancelar.');
                        botonCancelar.disabled = false;
                        botonCancelar.textContent = 'Cancelar este cobro';

                        return;
                    }

                    siguePolling = false;
                    mostrarEstado(divCancelado);
                })
                .catch(function () {
                    alert('No se pudo conectar con el servidor.');
                    botonCancelar.disabled = false;
                    botonCancelar.textContent = 'Cancelar este cobro';
                });
        });

        function formatearMoneda(monto) {
            return '$' + monto.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function consultar(estadoUrl, redirectUrl) {
            if (! siguePolling) {
                return;
            }

            fetch(estadoUrl, { headers: { 'Accept': 'application/json' } })
                .then((respuesta) => respuesta.json())
                .then((datos) => {
                    if (! siguePolling) {
                        return;
                    }

                    if (datos.estado === 'aprobado') {
                        window.location.href = redirectUrl;

                        return;
                    }

                    if (datos.estado === 'expirado') {
                        siguePolling = false;
                        mostrarEstado(divExpirado);

                        return;
                    }

                    if (datos.estado === 'cancelado') {
                        siguePolling = false;
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

            if (enviando) {
                return;
            }

            enviando = true;
            siguePolling = true;
            cancelarUrl = null;

            const boton = form.querySelector('button[type="submit"]');
            if (boton) boton.disabled = true;

            botonCancelar.disabled = false;
            botonCancelar.textContent = 'Cancelar este cobro';

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
                        habilitarBoton();

                        return;
                    }

                    cancelarUrl = datos.cancelar_url;
                    montoTexto.innerHTML = 'Monto: <strong>' + formatearMoneda(datos.monto) + '</strong>';
                    mostrarEstado(divPendiente);
                    abrirModal();
                    setTimeout(function () { consultar(datos.estado_url, datos.redirect_url || redirectUrlDefault); }, 3000);
                })
                .catch(function () {
                    errorTexto.textContent = 'No se pudo conectar con el servidor.';
                    mostrarEstado(divError);
                    abrirModal();
                    habilitarBoton();
                });
        });

        // Si ya había un cobro QR pendiente para este pedido (ej: el cajero
        // recargó la página mientras esperaba el pago, lo que borra el
        // estado del modal en el navegador pero no cancela nada del lado
        // del servidor ni de Mercado Pago), se retoma la espera acá mismo
        // en vez de dejar el formulario como si nada estuviera pasando —
        // si no, el cajero vuelve a apretar "Confirmar" y el servidor le
        // rebota con "ya hay un cobro en curso" sin poder verlo ni
        // cancelarlo.
        if (intentoPendiente) {
            enviando = true;
            siguePolling = true;
            cancelarUrl = intentoPendiente.cancelar_url;

            const boton = form.querySelector('button[type="submit"]');
            if (boton) boton.disabled = true;

            montoTexto.innerHTML = 'Monto: <strong>' + formatearMoneda(intentoPendiente.monto) + '</strong>';
            mostrarEstado(divPendiente);
            abrirModal();
            consultar(intentoPendiente.estado_url, redirectUrlDefault);
        }
    })();
</script>
