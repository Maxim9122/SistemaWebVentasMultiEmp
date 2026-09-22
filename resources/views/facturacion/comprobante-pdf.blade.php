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
        .ticket p { margin: 2px 0; font-size: 10px; font-weight: bold; text-align: justify; }
        .ticket hr { border: 0.5px solid #000; margin: 5px 0; }
        .ticket .footer { text-align: center; font-size: 10px; }
        .detalle-linea { margin: 2px 0; font-size: 10px; }
    </style>
</head>
<body>
    <div class="ticket">
        <h1>{{ $empresa->razon_social }}</h1>
        <p>CUIT: {{ \App\Models\Empresa::formatearCuit($empresa->cuit) }}</p>
        @if ($empresa->direccion)
            <p>Domicilio: {{ $empresa->direccion }}</p>
        @endif
        @if ($empresa->telefono)
            <p>Tel: {{ $empresa->telefono }}</p>
        @endif
        @if ($empresa->condicionFiscalLegible())
            <p>{{ $empresa->condicionFiscalLegible() }}</p>
        @endif
        <hr>

        <p>Fecha: {{ $pedido->cobrado_at?->format('d-m-Y H:i') }}</p>
        <p>{{ $tituloComprobante }} (Cod {{ $codigoAfip }})</p>
        <p>P.Venta: {{ $puntoVenta }}&nbsp;&nbsp;&nbsp;Nro: {{ $numeroComprobante ?? '—' }}</p>
        <p>Venta N°: {{ $pedido->numero_venta }}</p>
        @if ($referenciaAsociada ?? null)
            <p>{{ $referenciaAsociada }}</p>
        @endif

        <p>
            Cliente:
            {{ $clienteCuit ? $clienteNombre.' — CUIT: '.\App\Models\Empresa::formatearCuit($clienteCuit) : 'Consumidor Final' }}
        </p>
        <p>Vendedor: {{ $pedido->vendedor->name }}</p>
        <p>Cajero: {{ $pedido->cajero->name ?? '—' }}</p>
        <hr>

        <h3>Detalle</h3>
        @foreach ($pedido->items as $item)
            <p class="detalle-linea">
                ({{ $item->cantidad }}) {{ $item->nombre_producto }} x ${{ number_format((float) $item->precio_unitario, 2, ',', '.') }}
                — ${{ number_format((float) $item->subtotal, 2, ',', '.') }}
            </p>
        @endforeach
        <hr>

        @if ($letra === 'A')
            <p>Subtotal (Neto): ${{ number_format($calculo['importe_neto'], 2, ',', '.') }}</p>
            <p>IVA (21%): ${{ number_format($calculo['importe_iva'], 2, ',', '.') }}</p>
            <p>Total: ${{ number_format($calculo['importe_total'], 2, ',', '.') }}</p>
        @elseif ($letra === 'C')
            <p>Total: ${{ number_format($calculo['importe_total'], 2, ',', '.') }}</p>
            <hr>
            <p>Monotributo — No discrimina IVA</p>
        @else
            <p>Total: ${{ number_format($calculo['importe_total'], 2, ',', '.') }}</p>
            <hr>
            <p>Reg. Transparencia fiscal al consumidor (Ley 27.743)</p>
            <p>IVA CONTENIDO (21%): ${{ number_format($calculo['importe_iva'], 2, ',', '.') }}</p>
        @endif
        <hr>

        <p>CAE: {{ $cae }}</p>
        <p>Vto. CAE: {{ $caeVencimiento?->format('d-m-Y') }}</p>
        <hr>

        @if ($qr ?? null)
            <div style="text-align: center; margin: 6px 0;">
                <img src="{{ $qr }}" width="110" height="110" alt="QR AFIP">
            </div>
            <hr>
        @endif

        <div class="footer">
            <p>Comprobante autorizado por AFIP/ARCA.</p>
            @if (($referenciaAsociada ?? null))
                <h3>Comprobante de crédito</h3>
            @else
                <h3>¡Gracias por su compra!</h3>
            @endif
        </div>

        @include('facturacion._pie-desarrollado-por')
    </div>
</body>
</html>
