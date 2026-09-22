@extends('layouts.app')

@section('titulo', 'Confirmar importación')

@php
    $etiquetas = [
        'nombre' => 'Nombre',
        'cuit' => 'CUIT / DNI',
        'telefono' => 'Teléfono',
        'email' => 'Email',
    ];
@endphp

@section('contenido')
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm text-slate-600 mb-4">
            Revisá que cada columna del Excel esté asignada al campo correcto. Ya propusimos una asignación —
            ajustá lo que haga falta. La próxima vez que subas un Excel con estos mismos encabezados, no vas a
            tener que hacer esto de nuevo.
        </p>

        <form method="POST" action="{{ route('clientes.importar.confirmar') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="overflow-x-auto">
                <table class="w-full text-sm mb-4">
                    <thead class="bg-slate-50 text-slate-500 text-left">
                        <tr>
                            <th class="px-3 py-2 font-medium">Columna del Excel</th>
                            <th class="px-3 py-2 font-medium">Ejemplo</th>
                            <th class="px-3 py-2 font-medium">Se importa como</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($encabezados as $indice => $encabezado)
                            <tr>
                                <td class="px-3 py-2 font-medium">{{ $encabezado !== '' ? $encabezado : '(sin nombre)' }}</td>
                                <td class="px-3 py-2 text-slate-500">{{ $preview[0][$indice] ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    <select name="mapeo[{{ $indice }}]" class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                                        <option value="">Ignorar esta columna</option>
                                        @foreach ($campos as $campo)
                                            <option value="{{ $campo }}" @selected(($sugerencias[$indice] ?? null) === $campo)>
                                                {{ $etiquetas[$campo] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex gap-2">
                <button type="button" id="abrir-modal-importar" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Importar
                </button>
                <a href="{{ route('clientes.importar.subir') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    <div id="modal-importar" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <h2 class="text-lg font-semibold mb-3">Confirmar importación</h2>

            <ul class="text-sm text-slate-600 space-y-2 mb-4">
                <li>Se van a procesar <strong>{{ $filasTotales }}</strong> {{ Str::plural('fila', $filasTotales) }} del archivo.</li>
                <li>Los clientes cuyo CUIT / DNI ya exista se van a <strong>actualizar</strong>; el resto se va a <strong>crear</strong>.</li>
            </ul>

            <p class="text-xs text-slate-400 mb-4">Esta acción queda registrada en el historial y se puede deshacer después desde "Importaciones".</p>

            <div class="flex gap-2 justify-end">
                <button type="button" id="cancelar-modal-importar" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </button>
                <button type="button" id="confirmar-modal-importar" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Confirmar importación
                </button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('modal-importar');
            const form = document.querySelector('form[action="{{ route('clientes.importar.confirmar') }}"]');

            document.getElementById('abrir-modal-importar').addEventListener('click', function () {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });

            document.getElementById('cancelar-modal-importar').addEventListener('click', function () {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });

            document.getElementById('confirmar-modal-importar').addEventListener('click', function () {
                form.submit();
            });
        })();
    </script>
@endsection
