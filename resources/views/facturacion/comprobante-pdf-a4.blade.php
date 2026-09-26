<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 30px 40px; }
        .encabezado { display: table; width: 100%; margin-bottom: 20px; }
        .encabezado .empresa { display: table-cell; width: 58%; vertical-align: top; }
        .encabezado .documento { display: table-cell; width: 42%; vertical-align: top; text-align: right; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .encabezado p { margin: 1px 0; font-size: 10px; color: #475569; }
        .caja-doc { display: inline-block; border: 1px solid #cbd5e1; border-radius: 4px; padding: 10px 14px; text-align: left; }
        .caja-doc .letra { display: inline-block; border: 1.5px solid #1e293b; border-radius: 3px; width: 20px; height: 20px; text-align: center; line-height: 20px; font-weight: bold; font-size: 13px; float: left; margin-right: 8px; }
        .caja-doc h2 { font-size: 14px; margin: 0 0 2px 28px; }
        .caja-doc p { margin: 1px 0 1px 28px; font-size: 10px; }
        hr { border: none; border-top: 1px solid #e2e8f0; margin: 14px 0; }
        .cliente { margin-top: 10px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.items th { text-align: left; font-size: 9px; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #cbd5e1; padding: 6px 4px; }
        table.items td { padding: 6px 4px; border-bottom: 1px solid #f1f5f9; font-size: 10px; }
        table.items .num { text-align: right; }
        .totales { margin-top: 14px; text-align: right; }
        .totales p { margin: 2px 0; font-size: 11px; }
        .totales .total { font-size: 15px; font-weight: bold; }
        .legal { margin-top: 10px; font-size: 9px; color: #64748b; }
        .pie-cae { display: table; width: 100%; margin-top: 20px; }
        .pie-cae .datos { display: table-cell; vertical-align: middle; font-size: 10px; }
        .pie-cae .qr { display: table-cell; width: 120px; text-align: right; vertical-align: middle; }
        .footer { margin-top: 20px; text-align: center; font-size: 10px; color: #475569; }
    </style>
</head>
<body>
    @include('facturacion._barra-imprimir')
    <div class="encabezado">
        <div class="empresa">
            <h1>{{ $empresa->razon_social }}</h1>
            <p>CUIT: {{ \App\Models\Empresa::formatearCuit($empresa->cuit) }}</p>
            @if ($empresa->direccion)
                <p>{{ $empresa->direccion }}</p>
            @endif
            @if ($empresa->telefono)
                <p>Tel: {{ $empresa->telefono }}</p>
            @endif
            @if ($empresa->condicionFiscalLegible())
                <p>{{ $empresa->condicionFiscalLegible() }}</p>
            @endif
        </div>
        <div class="documento">
            <div class="caja-doc">
                <span class="letra">{{ $letra }}</span>
                <h2>{{ $tituloComprobante }}</h2>
                <p>Cod {{ $codigoAfip }} — P.Venta {{ $puntoVenta }} — Nro {{ $numeroComprobante ?? '—' }}</p>
                <p>Fecha: {{ $pedido->cobrado_at?->format('d/m/Y H:i') }}</p>
                <p>Venta N°: {{ $pedido->numero_venta }}</p>
                @if ($referenciaAsociada ?? null)
                    <p>{{ $referenciaAsociada }}</p>
                @endif
            </div>
        </div>
    </div>

    <hr>

    <p class="cliente">
        <strong>Cliente:</strong>
        {{ $clienteCuit ? $clienteNombre.' — CUIT: '.\App\Models\Empresa::formatearCuit($clienteCuit) : 'Consumidor Final' }}
    </p>
    @if ($pedido->cajero && $pedido->vendedor_id === $pedido->cobrado_por)
        <p><strong>Cajero:</strong> {{ $pedido->cajero->name }}</p>
    @else
        <p><strong>Vendedor:</strong> {{ $pedido->vendedor->name }} &nbsp;&nbsp; <strong>Cajero:</strong> {{ $pedido->cajero->name ?? '—' }}</p>
    @endif
    @if ($pedido->esFiado())
        <p><strong>Cuenta corriente</strong></p>
    @endif

    <table class="items">
        <thead>
            <tr>
                <th>Producto</th>
                <th class="num">Cantidad</th>
                <th class="num">Precio unit.</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pedido->items as $item)
                <tr>
                    <td>{{ $item->nombre_producto }}</td>
                    <td class="num">{{ $item->cantidad }}</td>
                    <td class="num">${{ number_format((float) $item->precio_unitario, 2, ',', '.') }}</td>
                    <td class="num">${{ number_format((float) $item->subtotal, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totales">
        @if ($letra === 'A')
            <p>Subtotal (Neto): ${{ number_format($calculo['importe_neto'], 2, ',', '.') }}</p>
            <p>IVA (21%): ${{ number_format($calculo['importe_iva'], 2, ',', '.') }}</p>
            <p class="total">Total: ${{ number_format($calculo['importe_total'], 2, ',', '.') }}</p>
        @elseif ($letra === 'C')
            <p class="total">Total: ${{ number_format($calculo['importe_total'], 2, ',', '.') }}</p>
            <p class="legal" style="text-align: right;">Monotributo — No discrimina IVA</p>
        @else
            <p class="total">Total: ${{ number_format($calculo['importe_total'], 2, ',', '.') }}</p>
            <p class="legal" style="text-align: right;">
                Reg. Transparencia fiscal al consumidor (Ley 27.743)<br>
                IVA CONTENIDO (21%): ${{ number_format($calculo['importe_iva'], 2, ',', '.') }}
            </p>
        @endif
    </div>

    <hr>

    <div class="pie-cae">
        <div class="datos">
            <p><strong>CAE:</strong> {{ $cae }}</p>
            <p><strong>Vto. CAE:</strong> {{ $caeVencimiento?->format('d/m/Y') }}</p>
            <p class="legal">Comprobante autorizado por AFIP/ARCA.</p>
        </div>
        @if ($qr ?? null)
            <div class="qr">
                <img src="{{ $qr }}" width="100" height="100" alt="QR AFIP">
            </div>
        @endif
    </div>

    <div class="footer">
        @if (($referenciaAsociada ?? null))
            <p>Comprobante de crédito</p>
        @else
            <p>¡Gracias por su compra!</p>
        @endif
    </div>

    @include('facturacion._pie-desarrollado-por')
</body>
</html>
