<?php

namespace App\Services;

use App\Mail\TicketMailable;
use App\Models\Empresa;
use Illuminate\Support\Facades\Mail;

/**
 * Punto único para mandar por email el PDF de un ticket (venta o
 * presupuesto) — centraliza el armado del Mailable para no duplicarlo entre
 * VentaController y PresupuestoController. Nunca decide QUÉ PDF mandar (eso
 * lo arma cada caller con ComprobantePdfService, que ya conoce si es
 * factura/remito/presupuesto) — solo lo envía.
 */
class EnvioTicketService
{
    /**
     * @throws \Throwable  Si falla el transporte de mail (SMTP mal configurado,
     *                     host caído, etc.) — el caller decide cómo avisarlo.
     */
    public function enviarPorEmail(
        Empresa $empresa,
        string $emailDestino,
        string $tituloDocumento,
        string $numero,
        string $pdfContenido,
        string $pdfNombreArchivo,
    ): void {
        Mail::to($emailDestino)->send(new TicketMailable(
            empresaNombre: $empresa->razon_social,
            tituloDocumento: $tituloDocumento,
            numero: $numero,
            pdfContenido: $pdfContenido,
            pdfNombreArchivo: $pdfNombreArchivo,
        ));
    }
}
