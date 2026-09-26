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
        table.montos { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.montos th { text-align: left; font-size: 9px; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #cbd5e1; padding: 6px 4px; }
        table.montos td { padding: 6px 4px; border-bottom: 1px solid #f1f5f9; font-size: 10px; }
        table.montos .num { text-align: right; }
        .totales { margin-top: 14px; text-align: right; }
        .totales p { margin: 2px 0; font-size: 11px; }
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
                <h2>Comprobante de pago</h2>
                <p>Fecha: {{ $pago->created_at->format('d/m/Y H:i') }}</p>
                <p class="no-valido">No válido como factura</p>
            </div>
        </div>
    </div>

    <p><strong>Cliente:</strong> {{ $pago->cliente->nombre }}</p>
    <p><strong>Registrado por:</strong> {{ $pago->usuario->name }}</p>

    <table class="montos">
        <thead>
            <tr>
                <th>Medio de pago</th>
                <th class="num">Monto</th>
            </tr>
        </thead>
        <tbody>
            @if ($pago->monto_efectivo > 0)
                <tr>
                    <td>Efectivo</td>
                    <td class="num">${{ number_format((float) $pago->monto_efectivo, 2, ',', '.') }}</td>
                </tr>
            @endif
            @if ($pago->monto_tarjeta > 0)
                <tr>
                    <td>Tarjeta</td>
                    <td class="num">${{ number_format((float) $pago->monto_tarjeta, 2, ',', '.') }}</td>
                </tr>
            @endif
            @if ($pago->monto_transferencia > 0)
                <tr>
                    <td>Transferencia</td>
                    <td class="num">${{ number_format((float) $pago->monto_transferencia, 2, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="totales">
        <p class="total">Total pagado: ${{ number_format($pago->total(), 2, ',', '.') }}</p>
        <p>Saldo pendiente hoy: ${{ number_format($pago->cliente->saldoPendiente(), 2, ',', '.') }}</p>
    </div>

    <div class="footer">
        <p>¡Gracias!</p>
    </div>

    @include('facturacion._pie-desarrollado-por')
</body>
</html>
