<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            width: 220px;
        }
        .ticket { width: 100%; font-size: 12px; }
        h1 { font-size: 16px; text-align: center; margin: 3px 0; font-weight: bold; }
        h3 { text-align: center; margin: 3px 0; font-weight: bold; }
        h5 { text-align: center; margin: 0 0 3px; font-weight: bold; font-size: 9px; }
        .ticket p { margin: 2px 0; font-size: 10px; font-weight: bold; text-align: justify; }
        .ticket hr { border: 0.5px solid #000; margin: 5px 0; }
        .ticket .footer { text-align: center; font-size: 10px; }
        .detalle-linea { margin: 2px 0; font-size: 10px; }
    </style>
</head>
<body>
    <div class="ticket">
        <h3>Remito</h3>
        <h5>No válido como factura</h5>

        <h1>{{ $empresa->razon_social }}</h1>
        @if ($empresa->direccion)
            <p>Domicilio: {{ $empresa->direccion }}</p>
        @endif
        @if ($empresa->telefono)
            <p>Tel: {{ $empresa->telefono }}</p>
        @endif
        <hr>

        <p>Fecha: {{ $pedido->cobrado_at?->format('d-m-Y H:i') }}</p>
        <p>Venta N°: {{ $pedido->numero_venta }}</p>
        <p>Cliente: {{ $pedido->cliente_nombre }}</p>
        @if ($pedido->cajero && $pedido->vendedor_id === $pedido->cobrado_por)
            <p>Cajero: {{ $pedido->cajero->name }}</p>
        @else
            <p>Vendedor: {{ $pedido->vendedor->name }}</p>
            <p>Cajero: {{ $pedido->cajero->name ?? '—' }}</p>
        @endif
        @if ($pedido->esFiado())
            <p>Cuenta corriente</p>
        @endif
        <hr>

        <h3>Detalle</h3>
        @foreach ($pedido->items as $item)
            <p class="detalle-linea">
                ({{ $item->cantidad }}) {{ $item->nombre_producto }} x ${{ number_format((float) $item->precio_unitario, 2, ',', '.') }}
                — ${{ number_format((float) $item->subtotal, 2, ',', '.') }}
            </p>
        @endforeach
        <hr>

        <p>Total: ${{ number_format((float) $pedido->total, 2, ',', '.') }}</p>
        <hr>

        <div class="footer">
            <h3>¡Gracias por su compra!</h3>
        </div>

        @include('facturacion._pie-desarrollado-por')
    </div>
</body>
</html>
