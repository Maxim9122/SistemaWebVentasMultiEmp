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
    </style>
</head>
<body>
    <div class="reporte">
        <h1>{{ $empresa->razon_social }}</h1>
        <p class="subtitulo">Reporte de Productos — {{ $descripcionFiltro }}</p>
        <p class="meta">Generado el {{ now()->format('d/m/Y H:i') }} · {{ $productos->count() }} {{ Str::plural('producto', $productos->count()) }}</p>

        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Código</th>
                    <th>Categoría</th>
                    <th>Marca</th>
                    <th>Proveedor</th>
                    <th class="text-right">Precio</th>
                    <th class="text-right">Costo</th>
                    @if ($incluirStock)
                        <th class="text-right">Stock</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($productos as $producto)
                    <tr>
                        <td>{{ $producto->nombre }}</td>
                        <td>{{ $producto->codigo ?? '—' }}</td>
                        <td>{{ $producto->categoria ?? '—' }}</td>
                        <td>{{ $producto->marca ?? '—' }}</td>
                        <td>{{ $producto->proveedor?->nombre ?? '—' }}</td>
                        <td class="text-right">${{ number_format($producto->precio, 2, ',', '.') }}</td>
                        <td class="text-right">{{ $producto->costo !== null ? '$'.number_format($producto->costo, 2, ',', '.') : '—' }}</td>
                        @if ($incluirStock)
                            <td class="text-right">{{ $producto->stock }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $incluirStock ? 8 : 7 }}" style="text-align: center; padding: 12px; color: #64748b;">No hay productos que coincidan con el filtro.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @include('facturacion._pie-desarrollado-por')
    </div>
</body>
</html>
