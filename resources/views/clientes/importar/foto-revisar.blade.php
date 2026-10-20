@extends('layouts.app')

@section('titulo', 'Revisar clientes detectados')

@php
    $etiquetasCampos = [
        'nombre' => 'Nombre',
        'cuit' => 'CUIT / DNI',
        'telefono' => 'Teléfono',
        'email' => 'Email',
    ];
@endphp

@section('contenido')
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm text-slate-600 mb-1">
            Esto es lo que se pudo leer de la foto — revisá y corregí lo que haga falta antes de continuar.
            También podés sacar filas que no correspondan o agregar una a mano.
        </p>
        <p class="text-xs text-slate-400 mb-4">
            Esto todavía no importó nada — en el paso siguiente vas a poder confirmar el mapeo, igual que al
            importar un Excel. Los clientes sin CUIT/DNI se cargan igual, solo que no se pueden identificar como
            "ya existentes" la próxima vez.
        </p>

        <div class="overflow-x-auto -mx-6 px-6">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-slate-500 border-b">
                        @foreach ($etiquetasCampos as $etiqueta)
                            <th class="px-2 py-2 font-medium whitespace-nowrap">{{ $etiqueta }}</th>
                        @endforeach
                        <th class="px-2 py-2"></th>
                    </tr>
                </thead>
                <tbody id="filas_clientes" class="divide-y">
                    @foreach ($clientes as $cliente)
                        <tr>
                            @foreach ($campos as $campo)
                                <td class="px-2 py-1.5">
                                    <input type="text" data-campo="{{ $campo }}" value="{{ $cliente[$campo] ?? '' }}"
                                        class="w-40 rounded border border-slate-300 text-sm px-2 py-1 focus:border-slate-500 focus:ring-slate-500">
                                </td>
                            @endforeach
                            <td class="px-2 py-1.5">
                                <button type="button" class="btn-quitar-fila text-xs text-red-600 hover:underline whitespace-nowrap">Quitar</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <button type="button" id="btn_agregar_fila" class="mt-3 text-sm text-slate-600 hover:underline">
            + Agregar fila
        </button>

        <form method="POST" action="{{ route('clientes.importarFoto.confirmar') }}" id="form_confirmar_extraccion" class="mt-6 pt-4 border-t space-y-4">
            @csrf
            <input type="hidden" name="clientes_json" id="clientes_json">

            @error('clientes')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="flex gap-2">
                <button type="submit" id="btn_continuar" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Continuar
                </button>
                <a href="{{ route('clientes.importarFoto.subir') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Volver a sacar la foto
                </a>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const campos = @json($campos);
            const cuerpo = document.getElementById('filas_clientes');
            const btnAgregar = document.getElementById('btn_agregar_fila');
            const form = document.getElementById('form_confirmar_extraccion');
            const inputJson = document.getElementById('clientes_json');
            const btnContinuar = document.getElementById('btn_continuar');

            function filaVacia() {
                const fila = document.createElement('tr');

                campos.forEach(function (campo) {
                    const celda = document.createElement('td');
                    celda.className = 'px-2 py-1.5';

                    const input = document.createElement('input');
                    input.type = 'text';
                    input.dataset.campo = campo;
                    input.className = 'w-40 rounded border border-slate-300 text-sm px-2 py-1 focus:border-slate-500 focus:ring-slate-500';

                    celda.appendChild(input);
                    fila.appendChild(celda);
                });

                const celdaBoton = document.createElement('td');
                celdaBoton.className = 'px-2 py-1.5';
                const btnQuitar = document.createElement('button');
                btnQuitar.type = 'button';
                btnQuitar.className = 'btn-quitar-fila text-xs text-red-600 hover:underline whitespace-nowrap';
                btnQuitar.textContent = 'Quitar';
                celdaBoton.appendChild(btnQuitar);
                fila.appendChild(celdaBoton);

                return fila;
            }

            btnAgregar.addEventListener('click', function () {
                cuerpo.appendChild(filaVacia());
            });

            cuerpo.addEventListener('click', function (evento) {
                if (evento.target.classList.contains('btn-quitar-fila')) {
                    evento.target.closest('tr').remove();
                }
            });

            form.addEventListener('submit', function (evento) {
                const clientes = Array.from(cuerpo.querySelectorAll('tr')).map(function (fila) {
                    const datos = {};
                    fila.querySelectorAll('input[data-campo]').forEach(function (input) {
                        datos[input.dataset.campo] = input.value.trim();
                    });
                    return datos;
                }).filter(function (fila) {
                    return Object.values(fila).some(function (valor) { return valor !== ''; });
                });

                if (clientes.length === 0) {
                    evento.preventDefault();
                    alert('No hay ningún cliente cargado para importar.');
                    return;
                }

                inputJson.value = JSON.stringify(clientes);
                btnContinuar.disabled = true;
                btnContinuar.textContent = 'Continuando...';
            });
        })();
    </script>
@endsection
