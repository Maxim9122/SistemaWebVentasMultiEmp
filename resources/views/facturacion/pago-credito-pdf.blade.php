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
    </style>
</head>
<body>
    <div class="ticket">
        <h3>Comprobante de pago</h3>
        <h5>No válido como factura</h5>

        <h1>{{ $empresa->razon_social }}</h1>
        @if ($empresa->direccion)
            <p>Domicilio: {{ $empresa->direccion }}</p>
        @endif
        @if ($empresa->telefono)
            <p>Tel: {{ $empresa->telefono }}</p>
        @endif
        <hr>

        <p>Fecha: {{ $pago->created_at->format('d-m-Y H:i') }}</p>
        <p>Cliente: {{ $pago->cliente->nombre }}</p>
        <p>Registrado por: {{ $pago->usuario->name }}</p>
        <hr>

        <h3>Pago recibido</h3>
        @if ($pago->monto_efectivo > 0)
            <p>Efectivo: ${{ number_format((float) $pago->monto_efectivo, 2, ',', '.') }}</p>
        @endif
        @if ($pago->monto_tarjeta > 0)
            <p>Tarjeta: ${{ number_format((float) $pago->monto_tarjeta, 2, ',', '.') }}</p>
        @endif
        @if ($pago->monto_transferencia > 0)
            <p>Transferencia: ${{ number_format((float) $pago->monto_transferencia, 2, ',', '.') }}</p>
        @endif
        <hr>

        <p>Total pagado: ${{ number_format($pago->total(), 2, ',', '.') }}</p>
        <hr>

        <p>Saldo pendiente hoy: ${{ number_format($pago->cliente->saldoPendiente(), 2, ',', '.') }}</p>

        <div class="footer">
            <h3>¡Gracias!</h3>
        </div>

        @include('facturacion._pie-desarrollado-por')
    </div>
</body>
</html>
