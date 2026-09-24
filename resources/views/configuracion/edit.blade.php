@extends('layouts.app')

@section('titulo', 'Configuración')

@section('contenido')
    <div class="max-w-2xl bg-white rounded-lg shadow p-6 mb-4">
        <p class="font-medium text-slate-900 mb-3">Datos de la empresa</p>

        @if ($errors->any() && ($errors->has('razon_social') || $errors->has('cuit') || $errors->has('email_contacto') || $errors->has('datos_facturacion')))
            <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('configuracion.datos.update') }}" id="form_datos_empresa" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="razon_social" class="block text-sm font-medium mb-1">Razón social</label>
                <input id="razon_social" name="razon_social" type="text" value="{{ old('razon_social', $empresa->razon_social) }}" required
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="cuit" class="block text-sm font-medium mb-1">CUIT <span class="text-slate-400 font-normal">(opcional)</span></label>
                    <input id="cuit" name="cuit" type="text" value="{{ old('cuit', $empresa->cuit) }}"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    @if ($empresa->credencialFacturacion?->estaActiva())
                        <p class="text-xs text-amber-600 mt-1">Esta empresa ya tiene un certificado AFIP validado — si cambiás razón social/CUIT/email, la API de facturación va a rechazarlo.</p>
                    @elseif ($empresa->credencialFacturacion)
                        <p class="text-xs text-amber-600 mt-1">Si cambiás razón social/CUIT/email, se corrige automáticamente en la API de facturación (todavía no se completó el onboarding).</p>
                    @else
                        <p class="text-xs text-slate-400 mt-1">Solo hace falta si vas a facturar — es obligatorio recién al configurar la condición fiscal, más abajo.</p>
                    @endif
                </div>
                <div>
                    <label for="email_contacto" class="block text-sm font-medium mb-1">Email de contacto</label>
                    <input id="email_contacto" name="email_contacto" type="email" value="{{ old('email_contacto', $empresa->email_contacto) }}" required
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="telefono" class="block text-sm font-medium mb-1">Teléfono</label>
                    <input id="telefono" name="telefono" type="text" value="{{ old('telefono', $empresa->telefono) }}"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label for="direccion" class="block text-sm font-medium mb-1">Dirección</label>
                    <input id="direccion" name="direccion" type="text" value="{{ old('direccion', $empresa->direccion) }}"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
            </div>

            <button type="button" id="btn_guardar_datos_empresa" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                Guardar datos
            </button>
        </form>
    </div>

    @include('partials.modal-confirmacion', [
        'id' => 'modal_datos_empresa',
        'titulo' => 'Confirmar cambio de datos',
        'mensaje' => 'Revisá los datos antes de confirmar.',
        'textoConfirmar' => 'Guardar datos',
    ])

    <div class="max-w-2xl bg-white rounded-lg shadow p-6 mb-4">
        <p class="font-medium text-slate-900 mb-1">Logo de la empresa</p>
        <p class="text-sm text-slate-500 mb-3">Se muestra en el panel, abajo del nombre del sistema. Ideal: imagen cuadrada.</p>

        @if ($errors->has('logo'))
            <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ $errors->first('logo') }}
            </div>
        @endif

        <div class="flex items-center gap-4">
            @if ($empresa->logoUrl())
                <img src="{{ $empresa->logoUrl() }}" alt="Logo de {{ $empresa->razon_social }}" class="w-16 h-16 object-cover rounded-2xl border border-slate-200">
            @else
                <div class="w-16 h-16 rounded-2xl border border-dashed border-slate-300 flex items-center justify-center text-xs text-slate-400 text-center px-1">
                    Sin logo
                </div>
            @endif

            <form method="POST" action="{{ route('configuracion.logo.update') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                @csrf
                @method('PUT')
                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required
                    class="text-sm text-slate-600 file:mr-3 file:rounded file:border-0 file:bg-slate-900 file:text-white file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-slate-800">
                <button type="submit" class="rounded bg-slate-900 text-white px-3 py-1.5 text-sm font-medium hover:bg-slate-800">
                    {{ $empresa->logoUrl() ? 'Cambiar' : 'Subir' }}
                </button>
            </form>

            @if ($empresa->logoUrl())
                <form method="POST" action="{{ route('configuracion.logo.destroy') }}" onsubmit="return confirm('¿Quitar el logo de la empresa?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-slate-500 hover:text-red-600 hover:underline">Quitar</button>
                </form>
            @endif
        </div>
    </div>

    <div class="max-w-2xl bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('configuracion.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="permite_multiples_carritos" value="1"
                        @checked(old('permite_multiples_carritos', $empresa->permite_multiples_carritos)) class="rounded border border-slate-300 mt-0.5">
                    <span>
                        <span class="font-medium text-slate-900">Permitir varios carritos a la vez</span><br>
                        <span class="text-slate-500">Un vendedor o cajero vendedor puede atender más de un cliente en paralelo, sin perder lo que ya cargó en el carrito de otro.</span>
                    </span>
                </label>
            </div>

            <div>
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="permite_cambiar_precio_venta" value="1"
                        @checked(old('permite_cambiar_precio_venta', $empresa->permite_cambiar_precio_venta)) class="rounded border border-slate-300 mt-0.5">
                    <span>
                        <span class="font-medium text-slate-900">Permitir cambiar el precio al vender</span><br>
                        <span class="text-slate-500">Habilita la función en general. Además tenés que marcar, persona por persona, quién tiene el permiso en la sección Staff.</span>
                    </span>
                </label>
            </div>

            <div>
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="controla_stock" value="1"
                        @checked(old('controla_stock', $empresa->controla_stock)) class="rounded border border-slate-300 mt-0.5">
                    <span>
                        <span class="font-medium text-slate-900">Controlar stock</span><br>
                        <span class="text-slate-500">Si lo desmarcás, el sistema asume que siempre hay disponibilidad de todos los productos: no valida ni descuenta stock al cobrar, y el campo Stock se oculta en Productos. Útil si tu Excel de precios no trae cantidades.</span>
                    </span>
                </label>
            </div>

            <div>
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="permite_cajero_modificar_ventas" value="1"
                        @checked(old('permite_cajero_modificar_ventas', $empresa->permite_cajero_modificar_ventas)) class="rounded border border-slate-300 mt-0.5">
                    <span>
                        <span class="font-medium text-slate-900">Permitir que cajeros modifiquen ventas ya cobradas</span><br>
                        <span class="text-slate-500">El admin siempre puede editar una venta. Si activás esto, cajero y cajero vendedor también pueden — queda registrado qué usuario hizo cada modificación.</span>
                    </span>
                </label>
            </div>

            <div>
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="permite_cajero_notas_credito" value="1"
                        @checked(old('permite_cajero_notas_credito', $empresa->permite_cajero_notas_credito)) class="rounded border border-slate-300 mt-0.5">
                    <span>
                        <span class="font-medium text-slate-900">Permitir que cajeros anulen facturas (notas de crédito)</span><br>
                        <span class="text-slate-500">El admin siempre puede anular una factura ya aprobada. Activado por defecto — desmarcá si querés que solo el admin pueda hacerlo.</span>
                    </span>
                </label>
            </div>

            <div>
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="permite_fiado" value="1"
                        @checked(old('permite_fiado', $empresa->permite_fiado)) class="rounded border border-slate-300 mt-0.5">
                    <span>
                        <span class="font-medium text-slate-900">Permitir venta a crédito (fiado)</span><br>
                        <span class="text-slate-500">Si lo activás, al cobrar aparece "Fiado" como medio de pago (total o parcial) — exige elegir o cargar un cliente, que va a quedar debiendo esa plata. Se administra desde la sección Créditos.</span>
                    </span>
                </label>
            </div>

            <div>
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="factura_habilitada" value="1"
                        @checked(old('factura_habilitada', $empresa->factura_habilitada)) class="rounded border border-slate-300 mt-0.5">
                    <span>
                        <span class="font-medium text-slate-900">Habilitar facturación</span><br>
                        <span class="text-slate-500">Activado por defecto. Si lo desmarcás, en Caja/Carrito solo se puede elegir Remito — no borra tu condición fiscal ni el certificado AFIP ya configurado, útil para pausar la facturación temporalmente.</span>
                    </span>
                </label>
            </div>

            <div>
                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="mostrar_modal_comprobante" value="1"
                        @checked(old('mostrar_modal_comprobante', $empresa->mostrar_modal_comprobante)) class="rounded border border-slate-300 mt-0.5">
                    <span>
                        <span class="font-medium text-slate-900">Preguntar si descargar el comprobante al cobrar</span><br>
                        <span class="text-slate-500">Activado por defecto. Al confirmar una venta (remito o factura), aparece un cartel para descargar el PDF. Desmarcá esto si tu empresa no imprime ni descarga comprobantes.</span>
                    </span>
                </label>
            </div>

            <div class="border-t pt-5">
                <p class="font-medium text-slate-900 mb-1">Descuento / recargo por medio de pago</p>
                <p class="text-slate-500 text-sm mb-3">Un número negativo es descuento, positivo es recargo. Dejá 0 si ese medio no ajusta el precio.</p>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="ajuste_efectivo_porcentaje" class="block text-sm font-medium mb-1">Efectivo %</label>
                        <input id="ajuste_efectivo_porcentaje" name="ajuste_efectivo_porcentaje" type="number" step="0.01"
                            value="{{ old('ajuste_efectivo_porcentaje', $empresa->ajuste_efectivo_porcentaje) }}"
                            class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <div>
                        <label for="ajuste_tarjeta_porcentaje" class="block text-sm font-medium mb-1">Tarjeta %</label>
                        <input id="ajuste_tarjeta_porcentaje" name="ajuste_tarjeta_porcentaje" type="number" step="0.01"
                            value="{{ old('ajuste_tarjeta_porcentaje', $empresa->ajuste_tarjeta_porcentaje) }}"
                            class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <div>
                        <label for="ajuste_transferencia_porcentaje" class="block text-sm font-medium mb-1">Transferencia %</label>
                        <input id="ajuste_transferencia_porcentaje" name="ajuste_transferencia_porcentaje" type="number" step="0.01"
                            value="{{ old('ajuste_transferencia_porcentaje', $empresa->ajuste_transferencia_porcentaje) }}"
                            class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                </div>
            </div>

            <div class="border-t pt-5">
                <p class="font-medium text-slate-900 mb-1">Precio empleado</p>
                <p class="text-slate-500 text-sm mb-3">
                    Descuento aplicado al precio de catálogo cuando un cajero/cajero_vendedor carga un consumo interno
                    (egreso con producto). Dejalo vacío para usar el precio normal, sin descuento.
                </p>
                <div class="max-w-xs">
                    <label for="descuento_precio_empleado_porcentaje" class="block text-sm font-medium mb-1">Descuento %</label>
                    <input id="descuento_precio_empleado_porcentaje" name="descuento_precio_empleado_porcentaje" type="number" step="0.01" min="0" max="100"
                        value="{{ old('descuento_precio_empleado_porcentaje', $empresa->descuento_precio_empleado_porcentaje) }}"
                        placeholder="Sin descuento"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
            </div>

            <div class="border-t pt-5">
                <label for="condicion_fiscal" class="block font-medium text-slate-900 mb-1">Condición fiscal de la empresa</label>
                <p class="text-slate-500 text-sm mb-3">Necesaria para poder facturar (define si emitís factura A/B o C). Mientras no la configures, en Caja solo se puede hacer Remito.</p>
                @if (! $empresa->cuit)
                    <p class="text-xs text-amber-600 mb-2">Para elegir una condición fiscal primero necesitás cargar el CUIT de la empresa, arriba en "Datos de la empresa".</p>
                @endif
                @error('condicion_fiscal')
                    <p class="text-xs text-red-600 mb-2">{{ $message }}</p>
                @enderror
                <select id="condicion_fiscal" name="condicion_fiscal" class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Sin configurar</option>
                    <option value="responsable_inscripto" @selected(old('condicion_fiscal', $empresa->condicion_fiscal) === 'responsable_inscripto')>Responsable Inscripto</option>
                    <option value="monotributista" @selected(old('condicion_fiscal', $empresa->condicion_fiscal) === 'monotributista')>Monotributista</option>
                </select>
            </div>

            @php
                $condicionFiscalActual = old('condicion_fiscal', $empresa->condicion_fiscal);
                $comprobantePredeterminadoActual = old('comprobante_predeterminado', $empresa->comprobante_predeterminado);
            @endphp
            <div class="border-t pt-5">
                <label for="comprobante_predeterminado" class="block font-medium text-slate-900 mb-1">Comprobante preseleccionado al cobrar</label>
                <p class="text-slate-500 text-sm mb-3">El cajero siempre puede cambiarlo manualmente al cobrar — esto solo define qué viene marcado por defecto.</p>
                <select id="comprobante_predeterminado" name="comprobante_predeterminado" class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    <option value="remito" @selected($comprobantePredeterminadoActual === 'remito')>Remito</option>
                    @if ($condicionFiscalActual === 'responsable_inscripto')
                        <option value="B" @selected($comprobantePredeterminadoActual === 'B')>Factura B</option>
                    @elseif ($condicionFiscalActual === 'monotributista')
                        <option value="C" @selected($comprobantePredeterminadoActual === 'C')>Factura C</option>
                    @endif
                </select>
            </div>

            <div class="border-t pt-5">
                <label for="formato_comprobante" class="block font-medium text-slate-900 mb-1">Formato de impresión de comprobantes</label>
                <p class="text-slate-500 text-sm mb-3">
                    Aplica a todo lo que se descarga o imprime: ventas (remito o factura), presupuestos y comprobantes fiados.
                    <strong>Ticket</strong> es angosto, para impresora térmica de 80mm. <strong>A4</strong> es hoja entera, para impresora común.
                </p>
                <select id="formato_comprobante" name="formato_comprobante" class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    <option value="ticket" @selected(old('formato_comprobante', $empresa->formato_comprobante) === 'ticket')>Ticket (80mm)</option>
                    <option value="a4" @selected(old('formato_comprobante', $empresa->formato_comprobante) === 'a4')>A4 (hoja entera)</option>
                </select>
            </div>

            <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                Guardar
            </button>
        </form>

        @if ($empresa->puedeFacturar())
            <div class="border-t mt-5 pt-5">
                <p class="font-medium text-slate-900 mb-1">Facturación electrónica (AFIP/ARCA)</p>

                @if ($empresa->credencialFacturacion?->estaActiva())
                    <p class="text-sm text-emerald-700 mb-3">
                        Configurada — ambiente {{ $empresa->credencialFacturacion->ambiente }},
                        punto de venta {{ $empresa->credencialFacturacion->punto_venta }}.
                    </p>
                    <p class="text-xs text-amber-600 mb-3">
                        ¿Cambió el dueño, venció el certificado, o se comprometió la clave? Podés renovarlo acá abajo — cuando se complete
                        el certificado nuevo, la API Key actual se revoca automáticamente del otro lado (hasta ese momento sigue
                        funcionando sin cortes).
                    </p>
                @elseif ($empresa->credencialFacturacion?->estado === \App\Models\CredencialFacturacion::ESTADO_PENDIENTE_ONBOARDING)
                    <p class="text-sm text-amber-700 mb-3">
                        Se inició la configuración pero todavía no se completó. Si el link venció, volvé a iniciar el proceso.
                    </p>
                @else
                    <p class="text-slate-500 text-sm mb-3">
                        Conectá tu certificado AFIP para poder emitir comprobantes con CAE automáticamente al cobrar.
                    </p>
                @endif

                <form method="POST" action="{{ route('configuracion.facturacion.iniciar') }}" target="_blank" class="flex items-end gap-3">
                    @csrf
                    <div>
                        <label for="ambiente" class="block text-sm font-medium mb-1">Ambiente</label>
                        <select id="ambiente" name="ambiente" class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                            <option value="homologacion" @selected(old('ambiente', $empresa->credencialFacturacion?->ambiente) === 'homologacion')>Homologación (pruebas)</option>
                            <option value="produccion" @selected(old('ambiente', $empresa->credencialFacturacion?->ambiente) === 'produccion')>Producción</option>
                        </select>
                    </div>
                    <button type="submit" class="rounded {{ $empresa->credencialFacturacion?->estaActiva() ? 'bg-amber-600 hover:bg-amber-700' : 'bg-slate-900 hover:bg-slate-800' }} text-white px-4 py-2 text-sm font-medium">
                        @if ($empresa->credencialFacturacion?->estaActiva())
                            Renovar certificado
                        @elseif ($empresa->credencialFacturacion)
                            Reintentar configuración
                        @else
                            Configurar facturación electrónica
                        @endif
                    </button>
                </form>
                <p class="text-xs text-slate-400 mt-2">
                    Se abre en una pestaña nueva — cuando termines de cargar el certificado ahí, podés cerrarla y volver a esta pestaña
                    (actualizá la página para ver el estado actualizado).
                </p>

                @error('facturacion')
                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                @enderror
            </div>
        @endif
    </div>

    <script>
        (function () {
            const modal = document.getElementById('modal_datos_empresa');
            const mensaje = document.getElementById('modal_datos_empresa_mensaje');
            const cancelar = document.getElementById('modal_datos_empresa_cancelar');
            const confirmar = document.getElementById('modal_datos_empresa_confirmar');
            const form = document.getElementById('form_datos_empresa');

            const cuitOriginal = @json($empresa->cuit ?? '');
            const razonSocialOriginal = @json($empresa->razon_social);
            const emailOriginal = @json($empresa->email_contacto);

            document.getElementById('btn_guardar_datos_empresa').addEventListener('click', function () {
                const cuitNuevo = document.getElementById('cuit').value;
                const razonSocialNueva = document.getElementById('razon_social').value;
                const emailNuevo = document.getElementById('email_contacto').value;

                const cambioSensible = cuitNuevo !== cuitOriginal || razonSocialNueva !== razonSocialOriginal || emailNuevo !== emailOriginal;

                mensaje.textContent = cambioSensible
                    ? '¿Guardar los datos de la empresa? Como cambiás razón social, CUIT o email, esto también intenta corregirlo en la API de facturación si ya iniciaste el onboarding.'
                    : '¿Guardar los datos de la empresa?';

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });

            cancelar.addEventListener('click', function () {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });

            confirmar.addEventListener('click', function () {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                form.submit();
            });
        })();
    </script>
@endsection
