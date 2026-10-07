<div id="{{ $id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h2 class="text-lg font-semibold mb-3">{{ $titulo }}</h2>
        <p id="{{ $id }}_mensaje" class="text-sm text-slate-600 mb-4">{{ $mensaje }}</p>
        <div class="flex gap-2 justify-end">
            <button type="button" id="{{ $id }}_cancelar" class="rounded px-4 py-2 text-sm font-medium text-slate-600 hover:underline">
                Cancelar
            </button>
            <button type="button" id="{{ $id }}_confirmar" class="rounded {{ $claseConfirmar ?? 'bg-slate-900 hover:bg-slate-800' }} text-white px-4 py-2 text-sm font-medium">
                {{ $textoConfirmar ?? 'Confirmar' }}
            </button>
        </div>
    </div>
</div>
<script>
    (function () {
        // Deshabilita el botón un momento tras el click, para que un doble
        // click/toque no dispare la acción dos veces (ej. reembolsos o
        // anulaciones que tardan un ratito en responder). No es permanente
        // porque este mismo modal también se usa para exportar Excel/PDF,
        // donde el usuario puede querer volver a confirmar sin recargar.
        var boton = document.getElementById('{{ $id }}_confirmar');

        boton.addEventListener('click', function () {
            boton.disabled = true;
            boton.classList.add('opacity-50', 'cursor-not-allowed');

            setTimeout(function () {
                boton.disabled = false;
                boton.classList.remove('opacity-50', 'cursor-not-allowed');
            }, 2000);
        });
    })();
</script>
