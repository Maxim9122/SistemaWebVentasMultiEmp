@php
    $motivosParaBuscador = $motivosEgreso->map(fn ($m) => [
        'id' => $m->id,
        'nombre' => $m->nombre,
        'requiereProducto' => (bool) $m->requiere_producto,
        'soloProveedores' => (bool) $m->solo_proveedores,
    ])->values();

    $proveedoresParaBuscador = $proveedoresEgreso->map(fn ($p) => ['id' => $p->id, 'tipo' => 'proveedor', 'nombre' => $p->nombre])->values();
    $staffParaBuscador = $staffEgreso->map(fn ($u) => ['id' => $u->id, 'tipo' => 'usuario', 'nombre' => $u->name])->values();

    $productosParaBuscador = $productosEgreso->map(fn ($p) => [
        'id' => $p->id,
        'nombre' => $p->nombre,
        'codigo' => $p->codigo,
        'precio' => (float) $p->precio,
    ])->values();

    $descuentoEmpleado = auth()->user()->empresa->descuento_precio_empleado_porcentaje;
@endphp

<button type="button" id="btn_abrir_egreso"
    class="fixed bottom-20 right-6 z-40 rounded-full bg-slate-900 text-white px-4 py-2 text-sm font-medium shadow-lg hover:bg-slate-800">
    Egresos
</button>

<div id="modal_egreso" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold">Registrar egreso</h2>
            <button type="button" id="btn_cerrar_egreso" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>

        <button type="button" id="btn_ver_historial_egreso" class="text-sm text-slate-600 hover:underline mb-4">
            Ver historial de esta caja
        </button>

        <div id="historial_egreso_caja" class="hidden mb-4">
            @include('partials.tabla-egresos-caja', ['caja' => $caja])
        </div>

        <form method="POST" action="{{ route('egresos.store') }}" class="space-y-3">
            @csrf

            <div>
                <label for="motivo_egreso_id" class="block text-sm font-medium mb-1">Motivo</label>
                <select id="motivo_egreso_id" name="motivo_egreso_id" required
                    class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Elegí un motivo...</option>
                    @foreach ($motivosEgreso as $motivo)
                        <option value="{{ $motivo->id }}">{{ $motivo->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="relative">
                <label for="beneficiario_buscador" id="beneficiario_label" class="block text-sm font-medium mb-1">Beneficiario</label>
                <input type="text" id="beneficiario_buscador" autocomplete="off" placeholder="Buscar o escribir un nombre..."
                    class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                <ul id="beneficiario_resultados" class="hidden absolute z-10 mt-1 w-full max-h-40 overflow-auto rounded border border-slate-200 bg-white shadow-lg"></ul>
                <input type="hidden" id="proveedor_id" name="proveedor_id">
                <input type="hidden" id="beneficiario_user_id" name="beneficiario_user_id">
                <input type="hidden" id="beneficiario_nombre" name="beneficiario_nombre">
            </div>

            <div id="seccion_producto" class="hidden space-y-3">
                <div class="relative">
                    <label for="producto_buscador" class="block text-sm font-medium mb-1">Producto consumido</label>
                    <input type="text" id="producto_buscador" autocomplete="off" placeholder="Buscar producto..."
                        class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                    <ul id="producto_resultados" class="hidden absolute z-10 mt-1 w-full max-h-40 overflow-auto rounded border border-slate-200 bg-white shadow-lg"></ul>
                    <input type="hidden" id="producto_id" name="producto_id">
                </div>
                <div>
                    <label for="cantidad_consumo" class="block text-sm font-medium mb-1">Cantidad</label>
                    <input type="number" id="cantidad_consumo" name="cantidad" min="1" value="1"
                        class="w-24 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
                <p id="valor_consumo_preview" class="text-sm text-slate-500"></p>
            </div>

            <div id="seccion_montos" class="grid grid-cols-2 gap-3">
                <div>
                    <label for="monto_efectivo_egreso" class="block text-sm font-medium mb-1">Efectivo</label>
                    <input type="number" id="monto_efectivo_egreso" name="monto_efectivo" step="0.01" min="0"
                        class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label for="monto_transferencia_egreso" class="block text-sm font-medium mb-1">Transferencia</label>
                    <input type="number" id="monto_transferencia_egreso" name="monto_transferencia" step="0.01" min="0"
                        class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
            </div>

            <div>
                <label for="descripcion_egreso" class="block text-sm font-medium mb-1">Descripción (opcional)</label>
                <textarea id="descripcion_egreso" name="descripcion" rows="2"
                    class="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"></textarea>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Guardar egreso
                </button>
                <button type="button" id="btn_cancelar_egreso" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const motivos = @json($motivosParaBuscador);
        const proveedores = @json($proveedoresParaBuscador);
        const staff = @json($staffParaBuscador);
        const productos = @json($productosParaBuscador);
        const descuentoEmpleado = @json($descuentoEmpleado === null ? null : (float) $descuentoEmpleado);

        const btnAbrir = document.getElementById('btn_abrir_egreso');
        const btnCerrar = document.getElementById('btn_cerrar_egreso');
        const btnCancelar = document.getElementById('btn_cancelar_egreso');
        const modal = document.getElementById('modal_egreso');

        function abrir() { modal.classList.remove('hidden'); modal.classList.add('flex'); }
        function cerrar() { modal.classList.add('hidden'); modal.classList.remove('flex'); }

        btnAbrir.addEventListener('click', abrir);
        btnCerrar.addEventListener('click', cerrar);
        btnCancelar.addEventListener('click', cerrar);
        modal.addEventListener('click', function (evento) {
            if (evento.target === modal) cerrar();
        });

        const btnVerHistorial = document.getElementById('btn_ver_historial_egreso');
        const historial = document.getElementById('historial_egreso_caja');

        btnVerHistorial.addEventListener('click', function () {
            const visible = ! historial.classList.contains('hidden');
            historial.classList.toggle('hidden');
            btnVerHistorial.textContent = visible ? 'Ver historial de esta caja' : 'Ocultar historial';
        });

        // --- Beneficiario (proveedor / staff / nombre libre) ---
        const inputBenef = document.getElementById('beneficiario_buscador');
        const labelBenef = document.getElementById('beneficiario_label');
        const listaBenef = document.getElementById('beneficiario_resultados');
        const inputProveedor = document.getElementById('proveedor_id');
        const inputUsuario = document.getElementById('beneficiario_user_id');
        const inputNombre = document.getElementById('beneficiario_nombre');

        let resultadosBenef = [];
        let beneficiariosDisponibles = proveedores.concat(staff);

        function limpiarSeleccionBenef() {
            inputProveedor.value = '';
            inputUsuario.value = '';
            inputNombre.value = '';
        }

        function renderizarBenef() {
            listaBenef.innerHTML = '';

            if (resultadosBenef.length === 0) {
                listaBenef.classList.add('hidden');
                return;
            }

            resultadosBenef.forEach(function (b) {
                const li = document.createElement('li');
                li.textContent = b.nombre + (b.tipo === 'proveedor' ? ' (proveedor)' : ' (staff)');
                li.className = 'px-3 py-2 text-sm cursor-pointer hover:bg-slate-100';
                li.addEventListener('mousedown', function (evento) {
                    evento.preventDefault();
                    inputBenef.value = b.nombre;
                    limpiarSeleccionBenef();
                    if (b.tipo === 'proveedor') {
                        inputProveedor.value = b.id;
                    } else {
                        inputUsuario.value = b.id;
                    }
                    resultadosBenef = [];
                    renderizarBenef();
                });
                listaBenef.appendChild(li);
            });

            listaBenef.classList.remove('hidden');
        }

        inputBenef.addEventListener('input', function () {
            limpiarSeleccionBenef();
            inputNombre.value = inputBenef.value.trim();

            const texto = inputBenef.value.trim().toLowerCase();
            resultadosBenef = texto === '' ? [] : beneficiariosDisponibles.filter(function (b) {
                return b.nombre.toLowerCase().includes(texto);
            }).slice(0, 8);

            renderizarBenef();
        });

        inputBenef.addEventListener('blur', function () {
            setTimeout(function () { resultadosBenef = []; renderizarBenef(); }, 100);
        });

        // --- Producto consumido (solo motivos "requiere producto") ---
        const seccionProducto = document.getElementById('seccion_producto');
        const seccionMontos = document.getElementById('seccion_montos');
        const inputProdBuscador = document.getElementById('producto_buscador');
        const listaProd = document.getElementById('producto_resultados');
        const inputProductoId = document.getElementById('producto_id');
        const inputCantidad = document.getElementById('cantidad_consumo');
        const previewValor = document.getElementById('valor_consumo_preview');
        const inputMontoEfectivo = document.getElementById('monto_efectivo_egreso');
        const inputMontoTransferencia = document.getElementById('monto_transferencia_egreso');

        let resultadosProd = [];
        let productoElegido = null;

        function precioConDescuentoEmpleado(precioCatalogo) {
            if (descuentoEmpleado === null) return precioCatalogo;
            return Math.round(precioCatalogo * (1 - descuentoEmpleado / 100) * 100) / 100;
        }

        function formatearPrecio(precio) {
            return precio.toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function actualizarPreviewValor() {
            if (!productoElegido) {
                previewValor.textContent = '';
                return;
            }

            const cantidad = parseInt(inputCantidad.value, 10) || 0;
            const precioUnitario = precioConDescuentoEmpleado(productoElegido.precio);
            const total = precioUnitario * cantidad;

            previewValor.textContent = 'Precio: $' + formatearPrecio(precioUnitario) + ' · Total: $' + formatearPrecio(total)
                + (descuentoEmpleado !== null ? ' (con descuento empleado)' : '');
        }

        function renderizarProd() {
            listaProd.innerHTML = '';

            if (resultadosProd.length === 0) {
                listaProd.classList.add('hidden');
                return;
            }

            resultadosProd.forEach(function (p) {
                const li = document.createElement('li');
                li.textContent = p.nombre + (p.codigo ? ' (' + p.codigo + ')' : '');
                li.className = 'px-3 py-2 text-sm cursor-pointer hover:bg-slate-100';
                li.addEventListener('mousedown', function (evento) {
                    evento.preventDefault();
                    inputProdBuscador.value = p.nombre;
                    inputProductoId.value = p.id;
                    productoElegido = p;
                    resultadosProd = [];
                    renderizarProd();
                    actualizarPreviewValor();
                });
                listaProd.appendChild(li);
            });

            listaProd.classList.remove('hidden');
        }

        inputProdBuscador.addEventListener('input', function () {
            inputProductoId.value = '';
            productoElegido = null;
            actualizarPreviewValor();

            const texto = inputProdBuscador.value.trim().toLowerCase();
            resultadosProd = texto === '' ? [] : productos.filter(function (p) {
                return p.nombre.toLowerCase().includes(texto) || (p.codigo && p.codigo.toLowerCase().includes(texto));
            }).slice(0, 8);

            renderizarProd();
        });

        inputProdBuscador.addEventListener('blur', function () {
            setTimeout(function () { resultadosProd = []; renderizarProd(); }, 100);
        });

        inputCantidad.addEventListener('input', actualizarPreviewValor);

        // --- Cambiar de motivo reconfigura todo el formulario ---
        const selectMotivo = document.getElementById('motivo_egreso_id');

        function motivoActual() {
            return motivos.find(function (m) { return String(m.id) === selectMotivo.value; }) || null;
        }

        function actualizarSegunMotivo() {
            const motivo = motivoActual();

            // El beneficiario anterior puede no tener sentido para el nuevo motivo.
            inputBenef.value = '';
            limpiarSeleccionBenef();
            resultadosBenef = [];
            renderizarBenef();

            if (!motivo) {
                beneficiariosDisponibles = proveedores.concat(staff);
                labelBenef.textContent = 'Beneficiario';
                seccionProducto.classList.add('hidden');
                seccionMontos.classList.remove('hidden');
                return;
            }

            if (motivo.soloProveedores) {
                beneficiariosDisponibles = proveedores;
                labelBenef.textContent = 'Proveedor, o escribí un nombre';
            } else if (motivo.requiereProducto) {
                beneficiariosDisponibles = staff;
                labelBenef.textContent = 'Staff que consumió, o escribí un nombre';
            } else {
                beneficiariosDisponibles = proveedores.concat(staff);
                labelBenef.textContent = 'Proveedor, staff, o escribí un nombre';
            }

            if (motivo.requiereProducto) {
                seccionProducto.classList.remove('hidden');
                seccionMontos.classList.add('hidden');
                inputMontoEfectivo.value = '';
                inputMontoTransferencia.value = '';
            } else {
                seccionProducto.classList.add('hidden');
                seccionMontos.classList.remove('hidden');
                inputProdBuscador.value = '';
                inputProductoId.value = '';
                productoElegido = null;
                previewValor.textContent = '';
            }
        }

        selectMotivo.addEventListener('change', actualizarSegunMotivo);
        actualizarSegunMotivo();
    })();
</script>
