<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertaTriageMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $motivo;
    public string $detalle;
    public int $cantidadEspera;
    public int $maxEspera;

    public function __construct(
        string $motivo,
        string $detalle,
        int $cantidadEspera,
        int $maxEspera
    ) {
        $this->motivo         = $motivo;
        $this->detalle        = $detalle;
        $this->cantidadEspera = $cantidadEspera;
        $this->maxEspera      = $maxEspera;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🚨 Alerta Triage Urgencias — ' . $this->motivo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alerta-triage',
        );
    }
}