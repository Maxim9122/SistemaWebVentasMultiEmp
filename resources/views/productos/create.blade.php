@extends('layouts.app')

@section('titulo', 'Nuevo producto')

@section('contenido')
    <div class="max-w-lg bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('productos.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="nombre" class="block text-sm font-medium mb-1">Nombre</label>
                <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" required
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="codigo" class="block text-sm font-medium mb-1">Código / SKU</label>
                    <input id="codigo" name="codigo" type="text" value="{{ old('codigo') }}"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label for="categoria" class="block text-sm font-medium mb-1">Categoría</label>
                    <input id="categoria" name="categoria" type="text" value="{{ old('categoria') }}"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
            </div>

            <div>
                <label for="marca" class="block text-sm font-medium mb-1">Marca</label>
                <input id="marca" name="marca" type="text" value="{{ old('marca') }}"
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="precio" class="block text-sm font-medium mb-1">Precio</label>
                    <input id="precio" name="precio" type="number" step="0.01" min="0" value="{{ old('precio') }}" required
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label for="costo" class="block text-sm font-medium mb-1">Costo</label>
                    <input id="costo" name="costo" type="number" step="0.01" min="0" value="{{ old('costo') }}"
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                @if (auth()->user()->empresa->controla_stock)
                    <div>
                        <label for="stock" class="block text-sm font-medium mb-1">Stock</label>
                        <input id="stock" name="stock" type="number" step="1" min="0" value="{{ old('stock', 0) }}"
                            class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    </div>
                @else
                    <input type="hidden" name="stock" value="0">
                @endif
                <div class="@if (! auth()->user()->empresa->controla_stock) col-span-2 @endif">
                    <label for="unidad" class="block text-sm font-medium mb-1">Unidad</label>
                    <input id="unidad" name="unidad" type="text" value="{{ old('unidad') }}" placeholder="kg, unidad, caja..."
                        class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                </div>
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
            </div>

            @include('partials.campos-precio-por-cantidad', ['producto' => null])

            <div class="flex gap-2">
                <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Crear producto
                </button>
                <a href="{{ route('productos.index') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection
