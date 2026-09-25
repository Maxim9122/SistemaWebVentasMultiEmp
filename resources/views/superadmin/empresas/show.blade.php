@extends('layouts.app')

@section('titulo', $empresa->razon_social)

@section('contenido')
    <a href="{{ route('superadmin.empresas.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Volver</a>

    @if ($empresasConMismoCuit->isNotEmpty())
        <div class="mt-4 rounded border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-medium mb-1">⚠ El CUIT {{ $empresa->cuit }} también lo tiene:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($empresasConMismoCuit as $otra)
                    <li>
                        <a href="{{ route('superadmin.empresas.show', $otra) }}" class="underline hover:no-underline">{{ $otra->razon_social }}</a>
                        — {{ ucfirst($otra->estado) }}, registrada el {{ $otra->created_at->format('d/m/Y') }}
                    </li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs text-amber-700">
                Antes de aprobar, valdría la pena contactar al dueño de la otra empresa y confirmar si esta también le pertenece.
            </p>
        </div>
    @endif

    <div class="mt-4 bg-white rounded-lg shadow p-6 space-y-6">
        <div>
            <h2 class="text-sm font-semibold text-slate-700 mb-2">Datos de la empresa</h2>
            <dl class="grid grid-cols-2 gap-y-2 text-sm">
                <dt class="text-slate-500">Razón social</dt>
                <dd>{{ $empresa->razon_social }}</dd>
                <dt class="text-slate-500">CUIT</dt>
                <dd>{{ $empresa->cuit ?? '—' }}</dd>
                <dt class="text-slate-500">Rubro</dt>
                <dd>{{ $empresa->rubro ?? '—' }}</dd>
                <dt class="text-slate-500">Email de contacto</dt>
                <dd>{{ $empresa->email_contacto }}</dd>
                <dt class="text-slate-500">Teléfono</dt>
                <dd>{{ $empresa->telefono ?? '—' }}</dd>
                <dt class="text-slate-500">Dirección</dt>
                <dd>{{ $empresa->direccion ?? '—' }}</dd>
                <dt class="text-slate-500">Estado</dt>
                <dd class="font-medium">{{ ucfirst($empresa->estado) }}</dd>
                @if ($empresa->motivo_rechazo)
                    <dt class="text-slate-500">Motivo de rechazo</dt>
                    <dd>{{ $empresa->motivo_rechazo }}</dd>
                @endif
                @if ($empresa->motivo_suspension)
                    <dt class="text-slate-500">Motivo de suspensión</dt>
                    <dd>{{ $empresa->motivo_suspension }}</dd>
                @endif
            </dl>
        </div>

        <div>
            <h2 class="text-sm font-semibold text-slate-700 mb-2">Usuario administrador</h2>
            @foreach ($empresa->users as $usuario)
                <p class="text-sm">{{ $usuario->name }} — {{ $usuario->email }}</p>
            @endforeach
        </div>

        @if ($empresa->estado === 'activa')
            <div class="border-t pt-4">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-sm font-semibold text-slate-700">Abono mensual</h2>
                    @if ($empresa->abonoVencido())
                        <span class="text-xs font-medium rounded px-2 py-1 bg-red-100 text-red-800">
                            Vencido hace {{ $empresa->diasVencidoAbono() }} día(s)
                        </span>
                    @else
                        <span class="text-xs font-medium rounded px-2 py-1 bg-emerald-100 text-emerald-800">
                            Vigente hasta el {{ $empresa->vigenciaAbonoHasta()->format('d/m/Y') }}
                        </span>
                    @endif
                </div>

                <form method="POST" action="{{ route('superadmin.empresas.pagos.store', $empresa) }}" class="flex flex-wrap items-end gap-2 mb-4">
                    @csrf
                    <div>
                        <label for="fecha_pago" class="block text-xs text-slate-500 mb-1">Fecha del pago</label>
                        <input id="fecha_pago" name="fecha_pago" type="date" value="{{ old('fecha_pago', now()->format('Y-m-d')) }}" required
                            class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <div>
                        <label for="monto" class="block text-xs text-slate-500 mb-1">Monto pagado</label>
                        <input id="monto" name="monto" type="number" step="0.01" min="0.01" value="{{ old('monto') }}" required
                            class="w-32 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    </div>
                    <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                        Registrar pago
                    </button>
                </form>

                @if ($empresa->pagosAbono->isNotEmpty())
                    <table class="w-full text-sm">
                        <thead class="text-slate-500 text-left">
                            <tr>
                                <th class="py-1 font-medium">Fecha</th>
                                <th class="py-1 font-medium">Monto</th>
                                <th class="py-1 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($empresa->pagosAbono->sortByDesc('fecha_pago') as $pago)
                                <tr>
                                    <td class="py-1.5">{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                                    <td class="py-1.5">${{ number_format($pago->monto, 2, ',', '.') }}</td>
                                    <td class="py-1.5 text-right">
                                        <form method="POST" action="{{ route('superadmin.empresas.pagos.destroy', [$empresa, $pago]) }}" onsubmit="return confirm('¿Eliminar este pago? Esto puede volver a marcar la empresa como vencida.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-600 hover:underline">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-sm text-slate-500">Todavía no se registró ningún pago — vigente en período de gracia hasta el {{ $empresa->vigenciaAbonoHasta()->format('d/m/Y') }}.</p>
                @endif
            </div>
        @endif

        <div class="flex gap-2 pt-2 border-t">
            @if ($empresa->estado === 'pendiente')
                <form method="POST" action="{{ route('superadmin.empresas.aprobar', $empresa) }}">
                    @csrf
                    <button type="submit" class="rounded bg-emerald-600 text-white px-4 py-2 text-sm font-medium hover:bg-emerald-700">Aprobar</button>
                </form>
                <form method="POST" action="{{ route('superadmin.empresas.rechazar', $empresa) }}" class="flex items-center gap-2">
                    @csrf
                    <input type="text" name="motivo" placeholder="Motivo (opcional)" class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <button type="submit" class="rounded bg-red-600 text-white px-4 py-2 text-sm font-medium hover:bg-red-700">Rechazar</button>
                </form>
            @elseif ($empresa->estado === 'activa')
                <form method="POST" action="{{ route('superadmin.empresas.suspender', $empresa) }}" class="flex items-center gap-2">
                    @csrf
                    <input type="text" name="motivo" placeholder="Motivo (opcional)" class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <button type="submit" class="rounded bg-amber-600 text-white px-4 py-2 text-sm font-medium hover:bg-amber-700">Suspender</button>
                </form>
            @elseif (in_array($empresa->estado, ['rechazada', 'suspendida']))
                <form method="POST" action="{{ route('superadmin.empresas.reactivar', $empresa) }}">
                    @csrf
                    <button type="submit" class="rounded bg-emerald-600 text-white px-4 py-2 text-sm font-medium hover:bg-emerald-700">Reactivar</button>
                </form>
            @endif
        </div>
    </div>
@endsection
