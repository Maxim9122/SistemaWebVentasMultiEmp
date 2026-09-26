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
        <button type="button" onclick="history.back()">&larr; Volver</button>
        <button type="button" class="imprimir" onclick="window.print()">Imprimir</button>
    </div>
@endif
