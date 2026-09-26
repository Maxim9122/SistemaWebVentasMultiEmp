<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 30px 40px; }
        .encabezado { display: table; width: 100%; margin-bottom: 20px; }
        .encabezado .empresa { display: table-cell; width: 60%; vertical-align: top; }
        .encabezado .documento { display: table-cell; width: 40%; vertical-align: top; text-align: right; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .encabezado p { margin: 1px 0; font-size: 10px; color: #475569; }
        .caja-doc { display: inline-block; border: 1px solid #cbd5e1; border-radius: 4px; padding: 10px 14px; text-align: left; }
        .caja-doc h2 { font-size: 14px; margin: 0 0 2px; }
        .caja-doc p { margin: 1px 0; font-size: 10px; }
        .no-valido { font-size: 9px; color: #b45309; font-weight: bold; margin-top: 3px; }
        hr { border: none; border-top: 1px solid #e2e8f0; margin: 14px 0; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.items th { text-align: left; font-size: 9px; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #cbd5e1; padding: 6px 4px; }
        table.items td { padding: 6px 4px; border-bottom: 1px solid #f1f5f9; font-size: 10px; }
        table.items .num { text-align: right; }
        .totales { margin-top: 14px; text-align: right; }
        .totales .total { font-size: 15px; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #475569; }
    </style>
</head>
<body>
    @include('facturacion._barra-imprimir')
    <div class="encabezado">
        <div class="empresa">
            <h1>{{ $empresa->razon_social }}</h1>
            @if ($empresa->direccion)
                <p>{{ $empresa->direccion }}</p>
            @endif
            @if ($empresa->telefono)
                <p>Tel: {{ $empresa->telefono }}</p>
            @endif
        </div>
        <div class="documento">
            <div class="caja-doc">
                <h2>Remito</h2>
                <p>Venta N°: {{ $pedido->numero_venta }}</p>
                <p>Fecha: {{ $pedido->cobrado_at?->format('d/m/Y H:i') }}</p>
                <p class="no-valido">No válido como factura</p>
            </div>
        </div>
    </div>

    <p><strong>Cliente:</strong> {{ $pedido->cliente_nombre }}</p>
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
        <p class="total">Total: ${{ number_format((float) $pedido->total, 2, ',', '.') }}</p>
    </div>

    <div class="footer">
        <p>¡Gracias por su compra!</p>
    </div>

    @include('facturacion._pie-desarrollado-por')
</body>
</html>
