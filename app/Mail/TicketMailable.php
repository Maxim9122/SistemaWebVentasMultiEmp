<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email genérico para mandarle al cliente el ticket de una venta o de un
 * presupuesto en PDF — sin cola (mismo criterio que EmisionComprobanteService:
 * sin precedente de jobs en este proyecto, se envía sincrónico en el mismo
 * request). El PDF llega como adjunto (attachData, nunca se guarda en disco).
 */
class TicketMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $empresaNombre,
        public readonly string $tituloDocumento,
        public readonly string $numero,
        public readonly string $pdfContenido,
        public readonly string $pdfNombreArchivo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->tituloDocumento} N° {$this->numero} — {$this->empresaNombre}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket',
            with: [
                'empresaNombre' => $this->empresaNombre,
                'tituloDocumento' => $this->tituloDocumento,
                'numero' => $this->numero,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContenido, $this->pdfNombreArchivo)
                ->withMime('application/pdf'),
        ];
    }
}
