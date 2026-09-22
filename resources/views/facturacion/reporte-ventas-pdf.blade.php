<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; margin: 0; padding: 0; font-size: 11px; color: #1e293b; }
        .reporte { width: 100%; padding: 20px 24px; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .subtitulo { font-size: 12px; color: #475569; margin: 0 0 2px; }
        .meta { font-size: 10px; color: #64748b; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; }
        thead th { text-align: left; font-size: 9px; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #cbd5e1; padding: 4px 6px; }
        tbody td { padding: 4px 6px; border-bottom: 0.5px solid #e2e8f0; font-size: 10px; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .text-right { text-align: right; }
        tfoot td { padding: 6px; font-size: 11px; font-weight: bold; border-top: 1px solid #cbd5e1; }
    </style>
</head>
<body>
    <div class="reporte">
        <h1>{{ $empresa->razon_social }}</h1>
        <p class="subtitulo">Reporte de Ventas — {{ $descripcionFiltro }}</p>
        <p class="meta">Generado el {{ now()->format('d/m/Y H:i') }} · {{ $ventas->count() }} {{ Str::plural('venta', $ventas->count()) }}</p>

        <table>
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Comprobante</th>
                    <th>Vendedor</th>
                    <th>Cajero</th>
                    <th>Medio de pago</th>
                    <th class="text-right">Total cobrado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ventas as $venta)
                    <tr>
                        <td>{{ $venta->numero_venta }}</td>
                        <td>{{ $venta->cobrado_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $venta->factura?->cliente_nombre ?? $venta->cliente_nombre }}</td>
                        <td>{{ $venta->tipo_comprobante === 'factura' ? 'Factura '.$venta->factura?->tipo_factura : 'Remito' }}</td>
                        <td>{{ $venta->vendedor->name }}</td>
                        <td>{{ $venta->cajero->name ?? '—' }}</td>
                        <td>{{ ucfirst($venta->forma_pago ?? '—') }}</td>
                        <td class="text-right">${{ number_format($venta->total_cobrado, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 12px; color: #64748b;">No hay ventas que coincidan con el filtro.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7">Total</td>
                    <td class="text-right">${{ number_format($totalGeneral, 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        @include('facturacion._pie-desarrollado-por')
    </div>
</body>
</html>
