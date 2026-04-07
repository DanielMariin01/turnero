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
            subject: 'Alerta Triage Urgencias - ' . $this->motivo,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<h2>Alerta Triage Urgencias</h2>' .
                '<p><strong>Motivo:</strong> ' . $this->motivo . '</p>' .
                '<p><strong>Detalle:</strong> ' . $this->detalle . '</p>' .
                '<p><strong>Pacientes en espera:</strong> ' . $this->cantidadEspera . '</p>' .
                '<p><strong>Tiempo maximo de espera:</strong> ' . $this->maxEspera . ' minutos</p>' .
                '<p><strong>Fecha y hora:</strong> ' . now()->format('d/m/Y H:i:s') . '</p>' .
                '<hr><small>Mensaje automatico - Sistema Digiturno Urgencias</small>',
        );
    }
}