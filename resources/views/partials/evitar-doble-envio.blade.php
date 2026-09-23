{{--
    Deshabilita el botón de submit apenas se envía un formulario, en TODO el
    sitio — un solo lugar en vez de repetir esto forma por forma, para que
    "cobrar", "nuevo presupuesto", etc. nunca se disparen dos veces por un
    doble click rápido (o por quedarse esperando y volver a apretar).

    Delegado en document + fase de burbuja a propósito: si el formulario ya
    tiene su propia validación en JS que cancela el envío (ej. "che, te
    olvidaste actualizar esta línea"), esa validación corre en el propio
    form ANTES de que este listener la vea burbujear — así que
    evento.defaultPrevented ya viene en true acá, y no se deshabilita nada
    quedando el botón trabado sin haberse enviado de verdad.

    El setTimeout(…, 0) es a propósito: deshabilitar el botón en el mismo
    tick del submit puede hacer que el navegador no mande su name/value
    (algunos forms distinguen qué botón se apretó por eso) — se espera al
    siguiente tick, cuando el envío ya arrancó, para recién ahí bloquearlo.
--}}
<script>
    document.addEventListener('submit', function (evento) {
        if (evento.defaultPrevented) {
            return;
        }

        const boton = evento.submitter && evento.submitter.tagName === 'BUTTON'
            ? evento.submitter
            : evento.target.querySelector('button[type="submit"]');

        if (!boton || boton.disabled) {
            return;
        }

        setTimeout(function () {
            boton.disabled = true;
            boton.classList.add('opacity-60', 'cursor-not-allowed');

            if (boton.children.length === 0) {
                boton.dataset.textoOriginal = boton.textContent;
                boton.textContent = 'Procesando...';
            }
        }, 0);
    });
</script>
