@extends('layouts.app')

@section('titulo', 'Venta #'.$pedido->numero_venta)

@section('contenido')
    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('ventas.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Volver a Ventas</a>
        @if ($puedeEditar)
            <a href="{{ route('ventas.edit', $pedido) }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                Editar venta
            </a>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-slate-500">Venta N°</p>
                <p class="font-medium">{{ $pedido->numero_venta }}</p>
            </div>
            <div>
                <p class="text-slate-500">Fecha</p>
                <p class="font-medium">{{ $pedido->cobrado_at?->format('d/m/Y H:i') }}</p>
            </div>
            <div>
                <p class="text-slate-500">Vendedor</p>
                <p class="font-medium">{{ $pedido->vendedor->name }}</p>
            </div>
            <div>
                <p class="text-slate-500">Cajero</p>
                <p class="font-medium">{{ $pedido->cajero->name ?? '—' }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 mb-4">
        @if ($pedido->tipo_comprobante === 'factura' && $pedido->factura)
            @php $factura = $pedido->factura; @endphp
            <p @class([
                'text-xs font-medium rounded px-2 py-1 inline-block mb-2',
                'bg-emerald-100 text-emerald-800' => $factura->estado === \App\Models\Factura::ESTADO_APROBADA,
                'bg-amber-100 text-amber-800' => $factura->estado === \App\Models\Factura::ESTADO_PENDIENTE_AFIP,
                'bg-red-100 text-red-800' => $factura->fallo(),
                'bg-slate-200 text-slate-700' => $factura->estado === \App\Models\Factura::ESTADO_PENDIENTE,
            ])>
                Factura {{ $factura->tipo_factura }} — {{ ucfirst($factura->estado) }}
            </p>
            <p class="font-medium">{{ $factura->cliente_nombre }}</p>
            <p class="text-sm text-slate-500">CUIT: {{ $factura->cliente_cuit ?? '—' }}</p>

            @if ($factura->cae)
                <p class="text-xs text-slate-500 mt-1">
                    CAE: {{ $factura->cae }} (vence {{ $factura->cae_vencimiento?->format('d/m/Y') }})
                </p>
                <a href="{{ route('ventas.comprobantePdf', $pedido) }}" target="_blank" class="inline-block mt-2 text-xs rounded px-3 py-1.5 border border-slate-300 hover:border-slate-400">
                    Ver / imprimir comprobante
                </a>
            @elseif ($factura->error_mensaje)
                <p class="text-xs text-red-600 mt-1">{{ $factura->error_mensaje }}</p>
            @endif

            @if ($factura->fallo())
                <form method="POST" action="{{ route('ventas.reintentarFacturacion', $pedido) }}" class="mt-2">
                    @csrf
                    <button type="submit" class="text-xs rounded px-3 py-1.5 border border-slate-300 hover:border-slate-400">
                        Reintentar facturación
                    </button>
                </form>
            @endif

            @if ($factura->estaAprobada())
                @if ($factura->notaCredito)
                    @php $notaCredito = $factura->notaCredito; @endphp
                    <div class="mt-4 pt-4 border-t">
                        <p @class([
                            'text-xs font-medium rounded px-2 py-1 inline-block mb-2',
                            'bg-emerald-100 text-emerald-800' => $notaCredito->estado === \App\Models\NotaCredito::ESTADO_APROBADA,
                            'bg-amber-100 text-amber-800' => $notaCredito->estado === \App\Models\NotaCredito::ESTADO_PENDIENTE_AFIP,
                            'bg-red-100 text-red-800' => $notaCredito->fallo(),
                            'bg-slate-200 text-slate-700' => $notaCredito->estado === \App\Models\NotaCredito::ESTADO_PENDIENTE,
                        ])>
                            Nota de Crédito {{ $factura->tipo_factura }} — {{ ucfirst($notaCredito->estado) }}
                        </p>
                        <p class="text-sm text-slate-500">Motivo: {{ $notaCredito->motivo }}</p>

                        @if ($notaCredito->cae)
                            <p class="text-xs text-slate-500 mt-1">
                                CAE: {{ $notaCredito->cae }} (vence {{ $notaCredito->cae_vencimiento?->format('d/m/Y') }})
                            </p>
                            <a href="{{ route('ventas.notaCreditoPdf', $pedido) }}" target="_blank" class="inline-block mt-2 text-xs rounded px-3 py-1.5 border border-slate-300 hover:border-slate-400">
                                Ver / imprimir nota de crédito
                            </a>
                        @elseif ($notaCredito->error_mensaje)
                            <p class="text-xs text-red-600 mt-1">{{ $notaCredito->error_mensaje }}</p>
                        @endif

                        @if ($notaCredito->fallo())
                            <form method="POST" action="{{ route('ventas.reintentarNotaCredito', $pedido) }}" class="mt-2">
                                @csrf
                                <button type="submit" class="text-xs rounded px-3 py-1.5 border border-slate-300 hover:border-slate-400">
                                    Reintentar nota de crédito
                                </button>
                            </form>
                        @endif
                    </div>
                @elseif ($puedeAnular)
                    <div class="mt-4 pt-4 border-t">
                        <button type="button" id="btn_anular_factura" class="text-xs rounded px-3 py-1.5 border border-red-300 text-red-700 hover:border-red-400">
                            Anular factura
                        </button>
                        <div id="bloque_anular_factura" class="hidden mt-2">
                            <label for="motivo_anular" class="block text-xs font-medium mb-1">Motivo de la anulación</label>
                            <textarea id="motivo_anular" rows="2" class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"></textarea>
                            <button type="button" id="btn_confirmar_anular" class="mt-2 text-xs rounded px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white">
                                Emitir nota de crédito
                            </button>
                        </div>
                        <form id="form_anular_factura" method="POST" action="{{ route('ventas.anularFactura', $pedido) }}" class="hidden">
                            @csrf
                            <input type="hidden" name="motivo" id="form_anular_factura_motivo">
                        </form>
                    </div>
                @endif
            @endif
        @else
            <p class="text-xs font-medium rounded px-2 py-1 inline-block bg-slate-200 text-slate-700 mb-2">Remito</p>
            <p class="font-medium">{{ $pedido->cliente_nombre }}</p>
            <a href="{{ route('ventas.remitoPdf', $pedido) }}" target="_blank" class="inline-block mt-2 text-xs rounded px-3 py-1.5 border border-slate-300 hover:border-slate-400">
                Ver / imprimir remito
            </a>
        @endif
    </div>

    @if ($ticketUrlFirmada)
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <p class="font-medium mb-3">Enviar comprobante</p>
            <div class="flex flex-wrap items-end gap-3">
                <form method="POST" action="{{ route('ventas.enviarEmail', $pedido) }}" class="flex items-end gap-2">
                    @csrf
                    <div>
                        <label for="email_envio" class="block text-xs text-slate-500 mb-1">Email del cliente</label>
                        <input type="email" name="email" id="email_envio" value="{{ old('email', $pedido->cliente?->email ?? $pedido->factura?->cliente?->email) }}" required
                            class="w-56 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <button type="submit" class="rounded px-3 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                        Enviar por email
                    </button>
                </form>

                <div class="flex items-end gap-2">
                    <div>
                        <label for="telefono_whatsapp" class="block text-xs text-slate-500 mb-1">Teléfono (WhatsApp, Argentina)</label>
                        <div class="flex items-center rounded border border-slate-300 focus-within:border-slate-500 focus-within:ring-1 focus-within:ring-slate-500">
                            <span class="pl-2 pr-1 text-sm text-slate-500 select-none">+54 9</span>
                            <input type="text" id="telefono_whatsapp" inputmode="numeric" maxlength="10"
                                value="{{ $pedido->cliente?->telefonoSoloDigitos() ?? $pedido->factura?->cliente?->telefonoSoloDigitos() }}" placeholder="1123456789"
                                class="w-28 rounded-r border-0 text-sm py-2 focus:ring-0">
                        </div>
                    </div>
                    <button type="button" id="btn_whatsapp" class="rounded px-3 py-2 text-sm font-medium text-emerald-700 border border-emerald-300 hover:border-emerald-400">
                        Enviar por WhatsApp
                    </button>
                </div>
            </div>
        </div>

        <script>
            (function () {
                const btnWhatsapp = document.getElementById('btn_whatsapp');
                const inputTelefono = document.getElementById('telefono_whatsapp');
                const ticketUrl = {{ Js::from($ticketUrlFirmada) }};
                const numero = {{ Js::from($pedido->numero_venta) }};

                inputTelefono.addEventListener('input', function () {
                    inputTelefono.value = inputTelefono.value.replace(/\D/g, '').slice(0, 10);
                });

                btnWhatsapp.addEventListener('click', function () {
                    const telefono = (inputTelefono.value || '').replace(/\D/g, '');

                    if (telefono.length !== 10) {
                        alert('Ingresá los 10 dígitos del teléfono (sin 0 ni 15) para poder enviarlo por WhatsApp.');
                        inputTelefono.focus();
                        return;
                    }

                    const mensaje = 'Te comparto el comprobante de la venta N° ' + numero + ': ' + ticketUrl;
                    const url = 'https://wa.me/549' + telefono + '?text=' + encodeURIComponent(mensaje);

                    window.open(url, '_blank');
                });
            })();
        </script>
    @endif

    @if ($pedido->facturas->count() > 1)
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <p class="font-medium text-slate-900 mb-2">Facturación anterior</p>
            <div class="space-y-2">
                @foreach ($pedido->facturas->where('id', '!=', $pedido->factura_id) as $facturaVieja)
                    <div class="text-sm border-t pt-2 first:border-t-0 first:pt-0">
                        <p>
                            Factura {{ $facturaVieja->tipo_factura }} — {{ ucfirst($facturaVieja->estado) }}
                            @if ($facturaVieja->cae)
                                (CAE {{ $facturaVieja->cae }})
                            @endif
                        </p>
                        @if ($facturaVieja->notaCredito)
                            <p class="text-slate-500">
                                Nota de Crédito: {{ ucfirst($facturaVieja->notaCredito->estado) }}
                                @if ($facturaVieja->notaCredito->cae)
                                    (CAE {{ $facturaVieja->notaCredito->cae }})
                                @endif
                                — {{ $facturaVieja->notaCredito->motivo }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden mb-4">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">Producto</th>
                    <th class="px-4 py-2 font-medium text-right">Cantidad</th>
                    <th class="px-4 py-2 font-medium text-right">Precio unitario</th>
                    <th class="px-4 py-2 font-medium text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($pedido->items as $item)
                    <tr>
                        <td class="px-4 py-2">
                            {{ $item->nombre_producto }}
                            @if ($item->tienePrecioEspecial())
                                <span class="block text-xs text-amber-600">Precio especial</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">{{ $item->cantidad }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($item->precio_unitario, 2, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right">${{ number_format($item->subtotal, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <p class="font-medium mb-2">Pago</p>
        <div class="text-sm text-slate-600 space-y-1">
            @if ($pedido->monto_efectivo)
                <p>Efectivo: ${{ number_format($pedido->monto_efectivo, 2, ',', '.') }} ({{ $pedido->ajuste_efectivo_porcentaje > 0 ? '+' : '' }}{{ $pedido->ajuste_efectivo_porcentaje }}%)</p>
            @endif
            @if ($pedido->monto_tarjeta)
                <p>Tarjeta: ${{ number_format($pedido->monto_tarjeta, 2, ',', '.') }} ({{ $pedido->ajuste_tarjeta_porcentaje > 0 ? '+' : '' }}{{ $pedido->ajuste_tarjeta_porcentaje }}%)</p>
            @endif
            @if ($pedido->monto_transferencia)
                <p>Transferencia: ${{ number_format($pedido->monto_transferencia, 2, ',', '.') }} ({{ $pedido->ajuste_transferencia_porcentaje > 0 ? '+' : '' }}{{ $pedido->ajuste_transferencia_porcentaje }}%)</p>
            @endif
        </div>
        <div class="border-t mt-3 pt-3 flex justify-between text-sm">
            <span class="text-slate-500">Total productos</span>
            <span>${{ number_format($pedido->total, 2, ',', '.') }}</span>
        </div>
        <div class="flex justify-between font-semibold text-lg">
            <span>Total cobrado</span>
            <span>${{ number_format($pedido->total_cobrado, 2, ',', '.') }}</span>
        </div>
    </div>

    @if ($pedido->modificaciones->isNotEmpty())
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-4 py-3 border-b bg-slate-50">
                <p class="font-medium">Historial de modificaciones</p>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-left">
                    <tr>
                        <th class="px-4 py-2 font-medium">Fecha</th>
                        <th class="px-4 py-2 font-medium">Usuario</th>
                        <th class="px-4 py-2 font-medium">Motivo</th>
                        <th class="px-4 py-2 font-medium text-right">Total anterior</th>
                        <th class="px-4 py-2 font-medium text-right">Total nuevo</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($pedido->modificaciones->sortByDesc('created_at') as $modificacion)
                        <tr>
                            <td class="px-4 py-2 text-slate-500">{{ $modificacion->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2">{{ $modificacion->user->name }}</td>
                            <td class="px-4 py-2 text-slate-500">{{ $modificacion->motivo ?? '—' }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format($modificacion->total_anterior, 2, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format($modificacion->total_nuevo, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($puedeAnular)
        @include('partials.modal-confirmacion', [
            'id' => 'modal_anular_factura',
            'titulo' => 'Anular factura',
            'mensaje' => 'Se va a emitir una nota de crédito por el total facturado y devolver el stock vendido. Esta acción no se puede deshacer. ¿Confirmás?',
            'textoConfirmar' => 'Sí, anular',
            'claseConfirmar' => 'bg-red-600 hover:bg-red-700',
        ])

        <script>
            (function () {
                var btnAnular = document.getElementById('btn_anular_factura');
                var bloque = document.getElementById('bloque_anular_factura');
                var btnConfirmarBloque = document.getElementById('btn_confirmar_anular');
                var motivoInput = document.getElementById('motivo_anular');
                var modal = document.getElementById('modal_anular_factura');
                var form = document.getElementById('form_anular_factura');
                var formMotivo = document.getElementById('form_anular_factura_motivo');

                if (!btnAnular) {
                    return;
                }

                btnAnular.addEventListener('click', function () {
                    bloque.classList.remove('hidden');
                    btnAnular.classList.add('hidden');
                });

                btnConfirmarBloque.addEventListener('click', function () {
                    if (!motivoInput.value.trim()) {
                        motivoInput.focus();
                        return;
                    }

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                });

                document.getElementById('modal_anular_factura_cancelar').addEventListener('click', function () {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                });

                document.getElementById('modal_anular_factura_confirmar').addEventListener('click', function () {
                    formMotivo.value = motivoInput.value.trim();
                    form.submit();
                });
            })();
        </script>
    @endif
@endsection
