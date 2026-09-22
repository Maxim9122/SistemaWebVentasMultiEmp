@extends('layouts.app')

@section('titulo', 'Importar productos')

@section('contenido')
    <div class="max-w-lg bg-white rounded-lg shadow p-6">
        <p class="text-sm text-slate-600 mb-4">
            Subí el Excel (.xlsx, .xls) o CSV con tu listado de productos. En el siguiente paso vas a poder
            revisar y ajustar qué columna corresponde a cada dato antes de importar nada.
        </p>

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
@endsection
