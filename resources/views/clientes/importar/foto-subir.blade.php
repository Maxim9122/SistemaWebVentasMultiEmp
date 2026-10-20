@extends('layouts.app')

@section('titulo', 'Importar clientes desde una foto')

@section('contenido')
    <div class="max-w-lg bg-white rounded-lg shadow p-6">
        <p class="text-sm text-slate-600 mb-4">
            Sacale una foto a tu listado de clientes (una agenda, un cuaderno de fiado, un listado de contactos),
            o cargá una ya sacada — el sistema va a leerla e intentar reconocer cada cliente. En el siguiente paso
            vas a poder revisar y corregir lo que haga falta antes de importar nada.
        </p>

        <div class="mb-4 rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Esto usa un servicio externo de lectura de imágenes (tiene un costo chico por foto, a cargo del
            sistema). Si la foto sale borrosa o con mala luz, probablemente convenga sacarla de nuevo antes de
            subirla.
        </div>

        <form method="POST" action="{{ route('clientes.importarFoto.extraer') }}" enctype="multipart/form-data" class="space-y-4">
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

            <div class="flex gap-2">
                <button type="submit" id="btn_leer_foto" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Leer foto
                </button>
                <a href="{{ route('clientes.index') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
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
