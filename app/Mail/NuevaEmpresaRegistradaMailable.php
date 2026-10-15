<?php

namespace App\Mail;

use App\Models\Empresa;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Le avisa al superadmin que llegó una solicitud de alta de empresa nueva,
 * para que no dependa de entrar a revisar el panel manualmente. Sin cola
 * (mismo criterio que TicketMailable): se manda sincrónico, y el caller
 * (RegistroEmpresaController) decide qué hacer si falla — nunca debe romper
 * el alta en sí.
 */
class NuevaEmpresaRegistradaMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Empresa $empresa) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva empresa registrada: '.$this->empresa->razon_social,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nueva-empresa-registrada',
            with: [
                'empresa' => $this->empresa,
                'urlRevision' => route('superadmin.empresas.show', $this->empresa),
            ],
        );
    }
}
