@extends('layouts.app')

@section('titulo', 'Reponer stock desde una foto')

@section('contenido')
    <div class="max-w-lg bg-white rounded-lg shadow p-6">
        <p class="text-sm text-slate-600 mb-4">
            Sacale una foto al remito o factura del proveedor (o cargá una ya sacada) — el sistema va a leer los
            productos y cantidades y buscarlos en tu catálogo. En el siguiente paso vas a poder revisar, corregir el
            producto de cada línea y confirmar la reposición.
        </p>

        <div class="mb-4 rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Esto usa un servicio externo de lectura de imágenes (tiene un costo chico por foto, a cargo del
            sistema). Si la foto sale borrosa o con mala luz, probablemente convenga sacarla de nuevo antes de
            subirla.
        </div>

        <form method="POST" action="{{ route('productos.reposiciones.importarFoto.extraer') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label for="foto" class="block text-sm font-medium mb-1">Foto</label>
                <input id="foto" name="foto" type="file" accept="image/*" required
                    class="w-full text-sm rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                <p class="text-xs text-slate-400 mt-1">Máximo 8 MB. JPG, PNG o WEBP. Podés sacar una foto nueva o elegir una que ya tengas guardada.</p>
                @error('foto')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="proveedor_id" class="block text-sm font-medium mb-1">Proveedor (opcional)</label>
                <select id="proveedor_id" name="proveedor_id"
                    class="w-full rounded border border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    <option value="">— Sin especificar —</option>
                    @foreach ($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}" @selected(old('proveedor_id') == $proveedor->id)>{{ $proveedor->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" id="btn_leer_foto" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Leer foto
                </button>
                <a href="{{ route('productos.index') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', function () {
            const boton = document.getElementById('btn_leer_foto');
            boton.disabled = true;
            boton.textContent = 'Leyendo la foto...';
        });
    </script>
@endsection
