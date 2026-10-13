{{-- Solo se muestra cuando esta misma plantilla se renderiza como página
web normal (ver ComprobantePdfService::generarXHtml($x, modoWeb: true) y
los métodos "imprimir" de los controllers) — nunca cuando se genera el PDF
de verdad (email, WhatsApp, o el botón "Descargar"), donde $modoWeb no se
manda. La barra nunca sale impresa: @media print la oculta. --}}
@if ($modoWeb ?? false)
    <style>
        .barra-imprimir {
            position: sticky;
            top: 0;
            background: #0f172a;
            color: #fff;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            font-family: sans-serif;
            font-size: 14px;
            z-index: 10;
        }
        .barra-imprimir button {
            font-family: sans-serif;
            font-size: 14px;
            font-weight: 600;
            padding: 8px 14px;
            border-radius: 6px;
            border: 1px solid #475569;
            background: transparent;
            color: #fff;
            cursor: pointer;
        }
        .barra-imprimir button.imprimir {
            background: #fff;
            color: #0f172a;
            border-color: #fff;
        }
        @media print {
            .barra-imprimir {
                display: none;
            }
        }
    </style>
    <div class="barra-imprimir">
        {{--
            $volverUrl la decide cada controller/servicio que arma este
            comprobante (para factura/remito/NC siempre es Carritos, igual
            que tocar "Carritos" en el menú — para presupuesto y pago de
            crédito es su propia página, ver ComprobantePdfService).
            En PC esto se abre en pestaña nueva de verdad: window.close() la
            cierra y el usuario vuelve a ver, debajo, la página desde donde
            vino, tal cual estaba.
            En el celular NO se intenta cerrar nada: según el navegador,
            window.close() ahí puede no hacer nada o cerrar de más (hasta el
            navegador entero), así que directamente se navega a $volverUrl.
        --}}
        <button type="button" onclick="volverComprobante(this)">&larr; Volver</button>
        <button type="button" class="imprimir" onclick="window.print()">Imprimir</button>
    </div>
    <script>
        var destinoVolver = @json($volverUrl ?? url()->previous());
        var esMobile = /Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

        function volverComprobante(boton) {
            if (boton) {
                boton.disabled = true;
            }

            if (esMobile) {
                window.location.href = destinoVolver;
                return;
            }

            window.close();
            setTimeout(function () {
                window.location.href = destinoVolver;
            }, 150);
        }

        {{--
            Imprimir automático (opt-in por empresa, ver Configuración): dispara
            el diálogo de impresión solo, sin esperar el clic en "Imprimir", y
            al cerrarse ese diálogo (se haya impreso o cancelado) vuelve solo —
            mismo comportamiento que ya tenía el botón "Volver", nada nuevo ahí.
            Sin el modificador --kiosk-printing de Chrome configurado en esa PC
            esto de última sigue mostrando el diálogo nativo (solo que
            automático en vez de con un clic) — nunca bloquea poder imprimir.
            Nunca en celular: ahí no existe un diálogo de impresión real de la
            misma forma, y afterprint es poco confiable.
        --}}
        @if (($autoimprimir ?? false))
            if (! esMobile) {
                window.addEventListener('afterprint', function () {
                    volverComprobante(null);
                });
                window.print();
            }
        @endif
    </script>
@endif
