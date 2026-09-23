@extends('layouts.app')

@section('titulo', 'Detalle de reposición')

@section('contenido')
    <div class="mb-4">
        <a href="{{ route('productos.reposiciones.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Volver a Reposiciones</a>
    </div>

    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <p class="text-sm text-slate-500">
            {{ $reposicion->created_at->format('d/m/Y H:i') }} · Cargado por {{ $reposicion->user->name ?? '—' }}
        </p>
        @if ($reposicion->proveedor)
            <p class="text-sm text-slate-500">Proveedor: {{ $reposicion->proveedor->nombre }}</p>
        @endif
        @if ($reposicion->nota)
            <p class="text-sm text-slate-500">Nota: {{ $reposicion->nota }}</p>
        @endif

        <div class="mt-3">
            @if ($reposicion->fueRevertida())
                <span class="text-xs font-medium rounded px-2 py-1 bg-slate-200 text-slate-700">
                    Revertida el {{ $reposicion->revertida_at->format('d/m/Y H:i') }}
                </span>
            @else
                <form method="POST" action="{{ route('productos.reposiciones.deshacer', $reposicion) }}"
                    onsubmit="return confirm('¿Deshacer esta reposición? Se le va a restar a cada producto la cantidad que se sumó acá. Esta acción no se puede deshacer.');">
                    @csrf
                    <button type="submit" class="text-sm text-red-600 hover:underline">Deshacer reposición</button>
                </form>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2 font-medium">Producto</th>
                    <th class="px-4 py-2 font-medium text-right">Stock anterior</th>
                    <th class="px-4 py-2 font-medium text-right">Cantidad agregada</th>
                    <th class="px-4 py-2 font-medium text-right">Stock nuevo</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($reposicion->items as $item)
                    <tr>
                        <td class="px-4 py-2">
                            @if ($item->producto)
                                <a href="{{ route('productos.edit', $item->producto) }}" class="hover:underline">{{ $item->nombre_producto }}</a>
                            @else
                                {{ $item->nombre_producto }} <span class="text-xs text-slate-400">(producto eliminado)</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">{{ $item->cantidad_anterior }}</td>
                        <td class="px-4 py-2 text-right text-emerald-700">+{{ $item->cantidad_agregada }}</td>
                        <td class="px-4 py-2 text-right font-medium">{{ $item->cantidad_nueva }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
