@php
    $soloMedioPago = $soloMedioPago ?? false;
    $fiadoHabilitado = $empresa->permite_fiado && ! $soloMedioPago;
    $clientesParaBuscador = $clientes->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'cuit' => $c->cuit])->values();
    $ajustes = [
        'efectivo' => (float) $empresa->ajuste_efectivo_porcentaje,
        'tarjeta' => (float) $empresa->ajuste_tarjeta_porcentaje,
        'transferencia' => (float) $empresa->ajuste_transferencia_porcentaje,
    ];
    $idUnico = uniqid('cobro_');
    $comprobantePredeterminado = $empresa->comprobantePredeterminadoEfectivo();
@endphp

<div class="mb-4">
    <div class="flex items-center justify-between mb-2">
        <label class="block text-sm font-medium">Medio de pago</label>
        <button type="button" id="{{ $idUnico }}_toggle_dividir" class="text-sm text-slate-600 hover:underline">Dividir en varios medios</button>
    </div>

    <div id="{{ $idUnico }}_pago_simple">
        <select id="{{ $idUnico }}_medio_pago_simple" class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            <option value="efectivo" selected>Efectivo</option>
            <option value="tarjeta">Tarjeta</option>
            <option value="transferencia">Transferencia</option>
            @if ($fiadoHabilitado)
                <option value="fiado">Fiado (a crédito)</option>
            @endif
        </select>
    </div>

    <div id="{{ $idUnico }}_pago_dividido" class="hidden space-y-2">
        <div class="grid {{ $fiadoHabilitado ? 'grid-cols-4' : 'grid-cols-3' }} gap-3">
            <div>
                <label class="block text-xs text-slate-500 mb-1">Efectivo</label>
                <input type="number" id="{{ $idUnico }}_input_efectivo" step="0.01" min="0" placeholder="0.00"
                    class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Tarjeta</label>
                <input type="number" id="{{ $idUnico }}_input_tarjeta" step="0.01" min="0" placeholder="0.00"
                    class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Transferencia</label>
                <input type="number" id="{{ $idUnico }}_input_transferencia" step="0.01" min="0" placeholder="0.00"
                    class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            </div>
            @if ($fiadoHabilitado)
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Fiado</label>
                    <input type="number" id="{{ $idUnico }}_input_fiado" step="0.01" min="0" placeholder="0.00"
                        class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
            @endif
        </div>
        <p id="{{ $idUnico }}_texto_restante" class="text-sm font-medium"></p>
    </div>

    <input type="hidden" name="monto_efectivo" id="{{ $idUnico }}_monto_efectivo">
    <input type="hidden" name="monto_tarjeta" id="{{ $idUnico }}_monto_tarjeta">
    <input type="hidden" name="monto_transferencia" id="{{ $idUnico }}_monto_transferencia">
    <input type="hidden" name="monto_fiado" id="{{ $idUnico }}_monto_fiado">

    <div id="{{ $idUnico }}_desglose" class="mt-2 text-sm text-slate-600"></div>
</div>

@unless ($soloMedioPago)
    <div class="mb-4">
        <label for="{{ $idUnico }}_tipo_comprobante" class="block text-sm font-medium mb-1">Comprobante</label>
        <select id="{{ $idUnico }}_tipo_comprobante" name="tipo_comprobante" class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            <option value="remito" @selected($comprobantePredeterminado === 'remito')>Remito</option>
            @if ($empresa->facturacionHabilitada())
                <option value="factura" @selected($comprobantePredeterminado !== 'remito')>Factura</option>
            @endif
        </select>
        @unless ($empresa->facturacionHabilitada())
            @if (! $empresa->puedeFacturar())
                <p class="text-xs text-amber-600 mt-1">Configurá la condición fiscal de tu empresa en Configuración para poder facturar.</p>
            @else
                <p class="text-xs text-amber-600 mt-1">La facturación está desactivada temporalmente. Un admin puede reactivarla en Configuración.</p>
            @endif
        @endunless
    </div>

    @if ($empresa->facturacionHabilitada() || $fiadoHabilitado)
        <div id="{{ $idUnico }}_bloque_cliente" class="hidden space-y-3 border border-slate-200 rounded p-3 bg-slate-50 mb-4">
            <div class="relative">
                <label for="{{ $idUnico }}_buscador_cliente" class="block text-sm font-medium mb-1">Cliente</label>
                <p id="{{ $idUnico }}_nota_cliente" class="text-xs text-slate-400 mb-1">
                    Obligatorio para Factura A. Para B/C, dejalo vacío para facturar a Consumidor Final.
                </p>
                <input type="text" id="{{ $idUnico }}_buscador_cliente" autocomplete="off" placeholder="Buscar por nombre o CUIT..."
                    class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                <input type="hidden" name="cliente_id" id="{{ $idUnico }}_cliente_id">
                <ul id="{{ $idUnico }}_resultados_cliente" class="hidden absolute z-10 mt-1 w-full max-h-48 overflow-auto rounded border border-slate-200 bg-white shadow-lg"></ul>
                <button type="button" id="{{ $idUnico }}_toggle_nuevo_cliente" class="text-sm text-slate-600 hover:underline mt-1">+ Nuevo cliente</button>
            </div>

            <div id="{{ $idUnico }}_form_nuevo_cliente" class="hidden grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Nombre</label>
                    <input type="text" name="cliente_nombre" id="{{ $idUnico }}_cliente_nombre" class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">CUIT</label>
                    <input type="text" name="cliente_cuit" id="{{ $idUnico }}_cliente_cuit" class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label class="block text-xs text-slate-500 mb-1">Teléfono</label>
                    <input type="text" name="cliente_telefono" id="{{ $idUnico }}_cliente_telefono" class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
            </div>

            @if ($empresa->facturacionHabilitada())
                <div id="{{ $idUnico }}_bloque_tipo_factura" class="hidden">
                    <label class="block text-sm font-medium mb-1">Tipo de factura</label>
                    @if ($empresa->esResponsableInscripto())
                        <div class="flex gap-4 text-sm">
                            <label class="flex items-center gap-1">
                                <input type="radio" name="tipo_factura" value="A" @checked($comprobantePredeterminado === 'A')> Factura A
                            </label>
                            <label class="flex items-center gap-1">
                                <input type="radio" name="tipo_factura" value="B" @checked($comprobantePredeterminado !== 'A')> Factura B
                            </label>
                        </div>
                    @else
                        <p class="text-sm text-slate-600">Factura C</p>
                        <input type="hidden" name="tipo_factura" value="C">
                    @endif
                </div>
            @endif
        </div>
    @endif
@endunless

<script>
    (function () {
        const prefijo = {{ Js::from($idUnico) }};
        const total = {{ (float) $pedido->total }};
        const ajustes = @json($ajustes);
        const clientes = @json($clientesParaBuscador);
        const fiadoHabilitado = @json($fiadoHabilitado);

        const $ = (sufijo) => document.getElementById(prefijo + sufijo);

        function formatearMoneda(monto) {
            return '$' + monto.toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function calcularAjustado(monto, medio) {
            if (medio === 'fiado') return monto;
            return monto * (1 + (ajustes[medio] || 0) / 100);
        }

        // --- medios de pago ---
        const toggleDividir = $('_toggle_dividir');
        const pagoSimple = $('_pago_simple');
        const pagoDividido = $('_pago_dividido');
        const selectSimple = $('_medio_pago_simple');
        const inputEfectivo = $('_input_efectivo');
        const inputTarjeta = $('_input_tarjeta');
        const inputTransferencia = $('_input_transferencia');
        const inputFiado = $('_input_fiado');
        const hiddenEfectivo = $('_monto_efectivo');
        const hiddenTarjeta = $('_monto_tarjeta');
        const hiddenTransferencia = $('_monto_transferencia');
        const hiddenFiado = $('_monto_fiado');
        const textoRestante = $('_texto_restante');
        const desglose = $('_desglose');

        let modoDividido = false;

        function actualizarSimple() {
            const medio = selectSimple.value;
            hiddenEfectivo.value = medio === 'efectivo' ? total.toFixed(2) : '';
            hiddenTarjeta.value = medio === 'tarjeta' ? total.toFixed(2) : '';
            hiddenTransferencia.value = medio === 'transferencia' ? total.toFixed(2) : '';
            hiddenFiado.value = medio === 'fiado' ? total.toFixed(2) : '';

            const ajustado = calcularAjustado(total, medio);
            const pct = ajustes[medio];
            const detalle = pct ? ' (' + (pct > 0 ? '+' : '') + pct + '%)' : '';
            desglose.textContent = medio === 'fiado'
                ? 'Queda fiado: ' + formatearMoneda(total) + ' — no se cobra ahora'
                : 'Total a cobrar: ' + formatearMoneda(ajustado) + detalle;

            actualizarVisibilidadCliente();
        }

        function actualizarDividido() {
            const efectivo = parseFloat(inputEfectivo.value) || 0;
            const tarjeta = parseFloat(inputTarjeta.value) || 0;
            const transferencia = parseFloat(inputTransferencia.value) || 0;
            const fiado = inputFiado ? (parseFloat(inputFiado.value) || 0) : 0;
            const suma = efectivo + tarjeta + transferencia + fiado;
            const restante = Math.round((total - suma) * 100) / 100;

            hiddenEfectivo.value = efectivo > 0 ? efectivo.toFixed(2) : '';
            hiddenTarjeta.value = tarjeta > 0 ? tarjeta.toFixed(2) : '';
            hiddenTransferencia.value = transferencia > 0 ? transferencia.toFixed(2) : '';
            hiddenFiado.value = fiado > 0 ? fiado.toFixed(2) : '';

            if (restante > 0) {
                textoRestante.textContent = 'Restante: ' + formatearMoneda(restante);
                textoRestante.className = 'text-sm font-medium text-slate-600';
            } else if (restante < 0) {
                textoRestante.textContent = 'Te pasaste por ' + formatearMoneda(Math.abs(restante));
                textoRestante.className = 'text-sm font-medium text-red-600';
            } else {
                textoRestante.textContent = 'Total cubierto';
                textoRestante.className = 'text-sm font-medium text-emerald-600';
            }

            const partes = [];
            let totalAjustado = 0;

            [['efectivo', efectivo], ['tarjeta', tarjeta], ['transferencia', transferencia]].forEach(function (par) {
                const medio = par[0];
                const monto = par[1];

                if (monto <= 0) {
                    return;
                }

                const ajustado = calcularAjustado(monto, medio);
                totalAjustado += ajustado;
                const pct = ajustes[medio];
                const detalle = pct ? ' (' + (pct > 0 ? '+' : '') + pct + '%)' : '';
                const etiqueta = medio.charAt(0).toUpperCase() + medio.slice(1);
                partes.push(etiqueta + ': ' + formatearMoneda(ajustado) + detalle);
            });

            if (fiado > 0) {
                partes.push('Fiado: ' + formatearMoneda(fiado) + ' (no se cobra ahora)');
            }

            desglose.innerHTML = partes.length
                ? partes.join('<br>') + '<br><strong>Total a cobrar ahora: ' + formatearMoneda(totalAjustado) + '</strong>'
                : '';

            actualizarVisibilidadCliente();
        }

        toggleDividir.addEventListener('click', function () {
            modoDividido = !modoDividido;
            pagoSimple.classList.toggle('hidden', modoDividido);
            pagoDividido.classList.toggle('hidden', !modoDividido);
            toggleDividir.textContent = modoDividido ? 'Pagar con un solo medio' : 'Dividir en varios medios';

            if (modoDividido) {
                inputEfectivo.value = '';
                inputTarjeta.value = '';
                inputTransferencia.value = '';
                if (inputFiado) inputFiado.value = '';
                actualizarDividido();
            } else {
                actualizarSimple();
            }
        });

        selectSimple.addEventListener('change', actualizarSimple);
        [inputEfectivo, inputTarjeta, inputTransferencia].forEach(function (input) {
            input.addEventListener('input', actualizarDividido);
        });
        if (inputFiado) {
            inputFiado.addEventListener('input', actualizarDividido);
        }

        // --- comprobante / cliente / factura ---
        const selectComprobante = $('_tipo_comprobante');
        const bloqueCliente = $('_bloque_cliente');
        const bloqueTipoFactura = $('_bloque_tipo_factura');
        const notaCliente = $('_nota_cliente');

        function actualizarVisibilidadCliente() {
            if (!bloqueCliente) return;

            const esFactura = selectComprobante && selectComprobante.value === 'factura';
            const montoFiadoActual = parseFloat(hiddenFiado.value) || 0;

            bloqueCliente.classList.toggle('hidden', !esFactura && montoFiadoActual <= 0);

            if (bloqueTipoFactura) {
                bloqueTipoFactura.classList.toggle('hidden', !esFactura);
            }

            if (notaCliente) {
                if (esFactura && montoFiadoActual > 0) {
                    notaCliente.textContent = 'Obligatorio (vas a facturar y fiar a la vez). Para Factura A siempre hace falta CUIT.';
                } else if (montoFiadoActual > 0) {
                    notaCliente.textContent = 'Obligatorio: elegí o cargá el cliente que va a quedar debiendo esta plata.';
                } else {
                    notaCliente.textContent = 'Obligatorio para Factura A. Para B/C, dejalo vacío para facturar a Consumidor Final.';
                }
            }
        }

        actualizarSimple();

        if (selectComprobante) {
            selectComprobante.addEventListener('change', actualizarVisibilidadCliente);
        }

        // --- buscador de cliente ---
        const inputCliente = $('_buscador_cliente');
        const hiddenCliente = $('_cliente_id');
        const listaClientes = $('_resultados_cliente');
        const toggleNuevoCliente = $('_toggle_nuevo_cliente');
        const formNuevoCliente = $('_form_nuevo_cliente');

        if (inputCliente) {
            let resultadosCliente = [];
            let indiceActivoCliente = -1;

            function renderizarClientes() {
                listaClientes.innerHTML = '';

                if (resultadosCliente.length === 0) {
                    listaClientes.classList.add('hidden');
                    return;
                }

                resultadosCliente.forEach(function (cliente, indice) {
                    const li = document.createElement('li');
                    li.textContent = cliente.nombre + (cliente.cuit ? ' (' + cliente.cuit + ')' : '');
                    li.className = 'px-3 py-2 text-sm cursor-pointer' + (indice === indiceActivoCliente ? ' bg-slate-100' : '');
                    li.addEventListener('mousedown', function (evento) {
                        evento.preventDefault();
                        seleccionarCliente(cliente);
                    });
                    listaClientes.appendChild(li);
                });

                listaClientes.classList.remove('hidden');
            }

            function seleccionarCliente(cliente) {
                hiddenCliente.value = cliente.id;
                inputCliente.value = cliente.nombre + (cliente.cuit ? ' (' + cliente.cuit + ')' : '');
                resultadosCliente = [];
                indiceActivoCliente = -1;
                renderizarClientes();
            }

            inputCliente.addEventListener('input', function () {
                hiddenCliente.value = '';
                const texto = inputCliente.value.trim().toLowerCase();
                indiceActivoCliente = -1;

                resultadosCliente = texto === '' ? [] : clientes.filter(function (cliente) {
                    return cliente.nombre.toLowerCase().includes(texto) || (cliente.cuit && cliente.cuit.toLowerCase().includes(texto));
                }).slice(0, 8);

                renderizarClientes();
            });

            inputCliente.addEventListener('keydown', function (evento) {
                if (resultadosCliente.length === 0) {
                    return;
                }

                if (evento.key === 'ArrowDown') {
                    evento.preventDefault();
                    indiceActivoCliente = (indiceActivoCliente + 1) % resultadosCliente.length;
                    renderizarClientes();
                } else if (evento.key === 'ArrowUp') {
                    evento.preventDefault();
                    indiceActivoCliente = (indiceActivoCliente - 1 + resultadosCliente.length) % resultadosCliente.length;
                    renderizarClientes();
                } else if (evento.key === 'Enter' && indiceActivoCliente >= 0) {
                    evento.preventDefault();
                    seleccionarCliente(resultadosCliente[indiceActivoCliente]);
                }
            });

            inputCliente.addEventListener('blur', function () {
                setTimeout(function () {
                    resultadosCliente = [];
                    renderizarClientes();
                }, 100);
            });
        }

        if (toggleNuevoCliente) {
            toggleNuevoCliente.addEventListener('click', function () {
                const visible = !formNuevoCliente.classList.contains('hidden');
                formNuevoCliente.classList.toggle('hidden', visible);
                toggleNuevoCliente.textContent = visible ? '+ Nuevo cliente' : 'Buscar cliente existente';

                if (!visible) {
                    hiddenCliente.value = '';
                    inputCliente.value = '';
                }
            });
        }
    })();
</script>
