@php
    $egresosCaja = $caja->egresos()->with(['motivo', 'proveedor', 'beneficiarioUsuario', 'producto'])->latest()->get();
@endphp

<div class="bg-white rounded-lg shadow overflow-hidden">
    <p class="px-4 py-2 text-sm font-medium border-b bg-slate-50">Egresos de esta caja ({{ $egresosCaja->count() }})</p>
    {{-- overflow-x-auto acá (no en el div de afuera): en el modal chico de
    "Registrar egreso" esta tabla de 6 columnas no entra nunca, antes
    quedaba directamente cortada sin poder verla entera. En las pantallas
    anchas donde se usa este mismo parcial (mi caja, historial de cajas)
    no cambia nada, porque ahí la tabla ya entra sin necesitar scroll. --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">Hora</th>
                    <th class="px-4 py-2 font-medium">Motivo</th>
                    <th class="px-4 py-2 font-medium">Beneficiario</th>
                    <th class="px-4 py-2 font-medium">Producto</th>
                    <th class="px-4 py-2 font-medium text-right">Efectivo</th>
                    <th class="px-4 py-2 font-medium text-right">Transferencia</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($egresosCaja as $egreso)
                    <tr>
                        <td class="px-4 py-2 text-slate-500 whitespace-nowrap">{{ $egreso->created_at->format('H:i') }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">{{ $egreso->motivo->nombre }}</td>
                        <td class="px-4 py-2 text-slate-500 whitespace-nowrap">{{ $egreso->beneficiarioLegible() ?? '—' }}</td>
                        <td class="px-4 py-2 text-slate-500 whitespace-nowrap">
                            {{ $egreso->producto ? $egreso->producto->nombre.' x'.$egreso->cantidad : '—' }}
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">{{ $egreso->monto_efectivo > 0 ? '$'.number_format($egreso->monto_efectivo, 2, ',', '.') : '—' }}</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">{{ $egreso->monto_transferencia > 0 ? '$'.number_format($egreso->monto_transferencia, 2, ',', '.') : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">Todavía no hay egresos cargados en esta caja.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
