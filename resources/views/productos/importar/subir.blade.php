@extends('layouts.app')

@section('titulo', 'Importar productos')

@section('contenido')
    <div class="max-w-lg bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between gap-3 mb-4">
            <p class="text-sm text-slate-600">
                Subí el Excel (.xlsx, .xls) o CSV con tu listado de productos. En el siguiente paso vas a poder
                revisar y ajustar qué columna corresponde a cada dato antes de importar nada.
            </p>
            <button type="button" onclick="document.getElementById('modal_guia_excel').classList.remove('hidden'); document.getElementById('modal_guia_excel').classList.add('flex');"
                class="shrink-0 text-xs font-medium text-slate-600 border border-slate-300 rounded px-3 py-1.5 hover:bg-slate-50 whitespace-nowrap">
                Guía rápida
            </button>
        </div>

        <form method="POST" action="{{ route('productos.importar.previsualizar') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label for="archivo" class="block text-sm font-medium mb-1">Archivo</label>
                <input id="archivo" name="archivo" type="file" accept=".xlsx,.xls,.csv" required
                    class="w-full text-sm rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                <p class="text-xs text-slate-400 mt-1">Máximo 5 MB.</p>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="proveedor_id" class="block text-sm font-medium">Proveedor</label>
                    <a href="{{ route('productos.proveedores.create') }}" target="_blank" class="text-xs text-slate-500 hover:underline">+ Nuevo proveedor</a>
                </div>
                <select id="proveedor_id" name="proveedor_id"
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    <option value="">— Sin proveedor —</option>
                    @foreach ($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}" @selected(old('proveedor_id') == $proveedor->id)>{{ $proveedor->nombre }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-400 mt-1">Se va a asignar a todos los productos creados o actualizados en esta importación.</p>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Continuar
                </button>
                <a href="{{ route('productos.index') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    @php
        $etiquetasCampos = [
            'nombre' => 'Nombre del producto',
            'codigo' => 'Código',
            'precio' => 'Precio de venta',
            'costo' => 'Costo',
            'stock' => 'Stock / cantidad',
            'categoria' => 'Categoría',
            'marca' => 'Marca',
            'unidad' => 'Unidad de medida',
        ];
        $sinonimosPorCampo = \App\Services\MapeoColumnasService::sinonimosPorCampo();
    @endphp

    <div id="modal_guia_excel" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6 max-h-[85vh] overflow-y-auto">
            <h2 class="text-lg font-semibold mb-3">Guía rápida para el Excel</h2>

            <p class="text-sm text-slate-600 mb-3">
                Antes de subir el archivo, dejalo lo más limpio posible: sacá títulos, logos, celdas combinadas,
                notas o filas en blanco antes o entre los datos. Idealmente el archivo debería tener
                <strong>una sola fila de encabezados</strong> (los nombres de las columnas) y, debajo, una fila
                por cada producto — nada más.
            </p>

            <p class="text-sm text-slate-600 mb-4">
                No hace falta que las columnas se llamen exactamente igual que en el sistema — reconocemos
                varios nombres habituales de forma automática, y en el paso siguiente vas a poder ajustar a mano
                cualquiera que no se haya reconocido bien (y el sistema recuerda el ajuste para la próxima vez).
            </p>

            <div class="space-y-3 mb-4">
                @foreach ($etiquetasCampos as $campo => $etiqueta)
                    <div class="border border-slate-200 rounded p-3">
                        <p class="text-sm font-medium text-slate-800">{{ $etiqueta }}</p>
                        <p class="text-xs text-slate-500 mt-1">
                            Se reconoce si tu columna se llama, por ejemplo:
                            {{ implode(', ', $sinonimosPorCampo[$campo] ?? []) }}.
                        </p>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end">
                <button type="button"
                    onclick="document.getElementById('modal_guia_excel').classList.add('hidden'); document.getElementById('modal_guia_excel').classList.remove('flex');"
                    class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Entendido
                </button>
            </div>
        </div>
    </div>
@endsection
