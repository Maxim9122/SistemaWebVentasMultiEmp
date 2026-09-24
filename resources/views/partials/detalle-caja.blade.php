<div class="bg-white rounded-lg shadow p-6 mb-4">
    <div class="grid grid-cols-2 gap-4 text-sm mb-4">
        <div>
            <p class="text-slate-500">Responsable</p>
            <p class="font-medium">{{ $caja->user->name }}</p>
        </div>
        <div>
            <p class="text-slate-500">Estado</p>
            <p class="font-medium">
                @if ($caja->estaAbierta())
                    <span class="text-xs font-medium rounded px-2 py-1 bg-emerald-100 text-emerald-800">Abierta</span>
                @else
                    <span class="text-xs font-medium rounded px-2 py-1 bg-slate-200 text-slate-700">Cerrada</span>
                @endif
            </p>
        </div>
        <div>
            <p class="text-slate-500">Abierta</p>
            <p class="font-medium">{{ $caja->abierta_at->format('d/m/Y H:i') }}</p>
        </div>
        <div>
            <p class="text-slate-500">Cerrada</p>
            <p class="font-medium">{{ $caja->cerrada_at?->format('d/m/Y H:i') ?? '—' }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 text-sm mb-4 border-t pt-4">
        <div>
            <p class="text-slate-500">Fondo inicial</p>
            <p class="font-medium">${{ number_format($caja->monto_apertura, 2, ',', '.') }}</p>
        </div>
        <div>
            <p class="text-slate-500">Ventas en efectivo</p>
            <p class="font-medium">${{ number_format($caja->totalVentasEfectivo(), 2, ',', '.') }}</p>
        </div>
        <div>
            <p class="text-slate-500">Ventas con tarjeta</p>
            <p class="font-medium">${{ number_format($caja->totalVentasTarjeta(), 2, ',', '.') }}</p>
        </div>
        <div>
            <p class="text-slate-500">Ventas por transferencia</p>
            <p class="font-medium">${{ number_format($caja->totalVentasTransferencia(), 2, ',', '.') }}</p>
        </div>
        <div>
            <p class="text-slate-500">Ventas con Mercado Pago</p>
            <p class="font-medium">${{ number_format($caja->totalVentasMercadopago(), 2, ',', '.') }}</p>
        </div>
        <div>
            <p class="text-slate-500">Egresos en efectivo</p>
            <p class="font-medium text-red-600">-${{ number_format($caja->totalEgresosEfectivo(), 2, ',', '.') }}</p>
        </div>
        <div>
            <p class="text-slate-500">Egresos por transferencia</p>
            <p class="font-medium text-red-600">-${{ number_format($caja->totalEgresosTransferencia(), 2, ',', '.') }}</p>
        </div>
    </div>

    <div class="border-t pt-4">
        <p class="text-slate-500 text-sm">Efectivo esperado</p>
        <p class="text-xl font-semibold">${{ number_format($caja->efectivoEsperado(), 2, ',', '.') }}</p>
        @if (! $caja->estaAbierta())
            <p class="text-sm text-slate-500 mt-2">
                Declarado al cerrar: <span class="font-medium">${{ number_format($caja->monto_cierre_declarado, 2, ',', '.') }}</span>
                — Diferencia:
                <span class="font-medium @class(['text-emerald-600' => $caja->diferencia() >= 0, 'text-red-600' => $caja->diferencia() < 0])">
                    {{ $caja->diferencia() >= 0 ? '+' : '-' }}${{ number_format(abs($caja->diferencia()), 2, ',', '.') }}
                </span>
            </p>
        @endif
    </div>
</div>

<div class="mb-4">
    @include('partials.tabla-egresos-caja', ['caja' => $caja])
</div>

@include('partials.pagos-credito-caja', ['caja' => $caja])
