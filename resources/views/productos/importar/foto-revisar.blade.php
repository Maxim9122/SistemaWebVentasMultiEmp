@extends('layouts.app')

@section('titulo', 'Revisar productos detectados')

@php
    $etiquetasCampos = [
        'nombre' => 'Nombre',
        'codigo' => 'Código',
        'precio' => 'Precio',
        'costo' => 'Costo',
        'stock' => 'Stock',
        'categoria' => 'Categoría',
        'marca' => 'Marca',
        'unidad' => 'Unidad',
    ];
@endphp

@section('contenido')
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-sm text-slate-600 mb-1">
            Esto es lo que se pudo leer de la foto — revisá y corregí lo que haga falta antes de continuar
            (un precio mal leído, un nombre incompleto, etc.). También podés sacar filas que no correspondan o
            agregar una a mano.
        </p>
        <p class="text-xs text-slate-400 mb-4">
            Esto todavía no importó nada — en el paso siguiente vas a poder elegir qué hacer con los productos
            que ya existan en tu catálogo (actualizar, ignorar, o crear como copia), igual que al importar un Excel.
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
                <tbody id="filas_productos" class="divide-y">
                    @foreach ($productos as $producto)
                        <tr>
                            @foreach ($campos as $campo)
                                <td class="px-2 py-1.5">
                                    <input type="text" data-campo="{{ $campo }}" value="{{ $producto[$campo] ?? '' }}"
                                        class="w-28 rounded border border-slate-300 text-sm px-2 py-1 focus:border-slate-500 focus:ring-slate-500">
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

        <form method="POST" action="{{ route('productos.importarFoto.confirmar') }}" id="form_confirmar_extraccion" class="mt-6 pt-4 border-t space-y-4">
            @csrf
            <input type="hidden" name="productos_json" id="productos_json">
            <input type="hidden" name="proveedor_id" value="{{ $proveedorId }}">

            @error('productos')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="flex gap-2">
                <button type="submit" id="btn_continuar" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Continuar
                </button>
                <a href="{{ route('productos.importarFoto.subir') }}" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Volver a sacar la foto
                </a>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const campos = @json($campos);
            const cuerpo = document.getElementById('filas_productos');
            const btnAgregar = document.getElementById('btn_agregar_fila');
            const form = document.getElementById('form_confirmar_extraccion');
            const inputJson = document.getElementById('productos_json');
            const btnContinuar = document.getElementById('btn_continuar');

            function filaVacia() {
                const fila = document.createElement('tr');

                campos.forEach(function (campo) {
                    const celda = document.createElement('td');
                    celda.className = 'px-2 py-1.5';

                    const input = document.createElement('input');
                    input.type = 'text';
                    input.dataset.campo = campo;
                    input.className = 'w-28 rounded border border-slate-300 text-sm px-2 py-1 focus:border-slate-500 focus:ring-slate-500';

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

            // Delegado en el tbody: cubre tanto las filas que vinieron de la
            // foto como las agregadas a mano después.
            cuerpo.addEventListener('click', function (evento) {
                if (evento.target.classList.contains('btn-quitar-fila')) {
                    evento.target.closest('tr').remove();
                }
            });

            form.addEventListener('submit', function (evento) {
                const productos = Array.from(cuerpo.querySelectorAll('tr')).map(function (fila) {
                    const datos = {};
                    fila.querySelectorAll('input[data-campo]').forEach(function (input) {
                        datos[input.dataset.campo] = input.value.trim();
                    });
                    return datos;
                }).filter(function (fila) {
                    return Object.values(fila).some(function (valor) { return valor !== ''; });
                });

                if (productos.length === 0) {
                    evento.preventDefault();
                    alert('No hay ningún producto cargado para importar.');
                    return;
                }

                inputJson.value = JSON.stringify(productos);
                btnContinuar.disabled = true;
                btnContinuar.textContent = 'Continuando...';
            });
        })();
    </script>
@endsection
