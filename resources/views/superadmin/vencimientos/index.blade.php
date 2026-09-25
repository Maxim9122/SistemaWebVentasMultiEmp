@extends('layouts.app')

@section('titulo', 'Vencimientos')

@section('contenido')
    <p class="text-sm text-slate-600 mb-4">
        Empresas activas cuyo abono mensual venció (30 días desde el último pago registrado, o desde el alta si
        todavía no pagaron nunca). Si pagaron más de un mes de una vez, cada pago suma 30 días — no aparecen acá
        hasta que se cumplan todos esos días.
    </p>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">Empresa</th>
                    <th class="px-4 py-2 font-medium">Venció el</th>
                    <th class="px-4 py-2 font-medium">Días vencido</th>
                    <th class="px-4 py-2 font-medium">Último pago</th>
                    <th class="px-4 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($empresas as $empresa)
                    @php
                        $ultimoPago = $empresa->pagosAbono->sortByDesc('fecha_pago')->first();
                        $telefono = $empresa->telefonoSoloDigitos();
                        $mensaje = "Hola {$empresa->razon_social}, te escribimos desde ".config('app.name')." para avisarte que tu abono mensual del sistema venció hace {$empresa->diasVencidoAbono()} día(s). Te pedimos que puedas regularizar el pago a la brevedad para seguir usando el sistema sin inconvenientes. ¡Muchas gracias!";
                        $urlWhatsapp = $telefono ? 'https://wa.me/549'.$telefono.'?text='.rawurlencode($mensaje) : null;
                    @endphp
                    <tr>
                        <td class="px-4 py-2">
                            <a href="{{ route('superadmin.empresas.show', $empresa) }}" class="font-medium hover:underline">{{ $empresa->razon_social }}</a>
                        </td>
                        <td class="px-4 py-2 text-slate-500">{{ $empresa->vigenciaAbonoHasta()->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">
                            <span class="text-xs font-medium rounded px-2 py-1 bg-red-100 text-red-800">
                                {{ $empresa->diasVencidoAbono() }} día(s)
                            </span>
                        </td>
                        <td class="px-4 py-2 text-slate-500">
                            @if ($ultimoPago)
                                {{ $ultimoPago->fecha_pago->format('d/m/Y') }} — ${{ number_format($ultimoPago->monto, 2, ',', '.') }}
                            @else
                                Nunca pagó
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">
                            @if ($urlWhatsapp)
                                <a href="{{ $urlWhatsapp }}" target="_blank" class="inline-flex items-center gap-1.5 rounded px-3 py-1.5 text-sm font-medium text-emerald-700 border border-emerald-300 hover:border-emerald-400">
                                    Avisar por WhatsApp
                                </a>
                            @else
                                <span class="text-xs text-slate-400">Sin teléfono registrado</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-slate-500">No hay empresas con el abono vencido.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
