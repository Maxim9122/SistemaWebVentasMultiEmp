{{--
    Globo flotante de ayuda: botón fijo abajo a la derecha que abre un panel
    chico (no tapa la pantalla) con el Centro de ayuda adentro, en un
    <iframe> — así la sección donde el usuario se quedó viendo la ayuda
    sobrevive a la navegación normal del sitio (cada click en el panel
    principal recarga la página completa, pero el iframe y su estado
    guardado en localStorage no se pierden). Se puede cerrar con la X sin
    perder el progreso: la próxima vez que se abre, vuelve a la misma
    sección/tema.
--}}
<button type="button" id="ayuda_flotante_boton" onclick="abrirAyudaFlotante()"
    class="fixed bottom-5 right-5 z-40 w-12 h-12 rounded-full bg-slate-900 text-white shadow-lg hover:bg-slate-800 flex items-center justify-center"
    aria-label="Abrir ayuda">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
</button>

<div id="ayuda_flotante_panel" class="hidden fixed bottom-5 right-5 z-40 w-[90vw] max-w-sm h-[30rem] max-h-[70vh] bg-white rounded-lg shadow-2xl border border-slate-200 flex flex-col overflow-hidden">
    <div class="shrink-0 flex items-center justify-between px-3 py-2 bg-slate-900 text-white">
        <span class="text-sm font-medium">Ayuda</span>
        <button type="button" onclick="cerrarAyudaFlotante()" class="text-slate-300 hover:text-white p-1" aria-label="Cerrar ayuda">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <iframe id="ayuda_flotante_iframe" class="flex-1 w-full" title="Centro de ayuda"></iframe>
</div>

<script>
    (function () {
        const boton = document.getElementById('ayuda_flotante_boton');
        const panel = document.getElementById('ayuda_flotante_panel');
        const iframe = document.getElementById('ayuda_flotante_iframe');
        const URL_DEFECTO = @json(route('ayuda.widget.index'));
        const CLAVE_ABIERTO = 'ayuda_flotante_abierto';
        const CLAVE_URL = 'ayuda_flotante_url';

        function leer(clave, porDefecto) {
            try {
                const valor = localStorage.getItem(clave);
                return valor === null ? porDefecto : valor;
            } catch (e) {
                return porDefecto;
            }
        }

        function guardar(clave, valor) {
            try {
                localStorage.setItem(clave, valor);
            } catch (e) {
                // Privado/bloqueado: el globo sigue funcionando dentro de esta
                // misma página, simplemente no recuerda la sección al navegar.
            }
        }

        window.abrirAyudaFlotante = function () {
            panel.classList.remove('hidden');
            boton.classList.add('hidden');
            guardar(CLAVE_ABIERTO, '1');

            if (!iframe.src) {
                iframe.src = leer(CLAVE_URL, URL_DEFECTO);
            }
        };

        window.cerrarAyudaFlotante = function () {
            panel.classList.add('hidden');
            boton.classList.remove('hidden');
            guardar(CLAVE_ABIERTO, '0');
        };

        iframe.addEventListener('load', function () {
            try {
                guardar(CLAVE_URL, iframe.contentWindow.location.href);
            } catch (e) {
                // Cross-origin u otra restricción rara — no rompe nada, solo
                // no se actualiza el recordatorio de sección.
            }
        });

        if (leer(CLAVE_ABIERTO, '0') === '1') {
            abrirAyudaFlotante();
        }
    })();
</script>
