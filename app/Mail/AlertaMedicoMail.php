<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class AlertaMedicoMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $motivo;
    public string $detalle;
    public int $cantidadDemorados;
    public int $maxDemora;
    public string $numero_turno;
    public string $paciente_urgencias;
    public string $tiempoFormateado; // ← agregar

    public function __construct(
        string $motivo,
        string $detalle,
        int $cantidadDemorados,
        int $maxDemora,
        string $numero_turno,
        string $paciente_urgencias,
        string $tiempoFormateado
    ) {
        $this->motivo              = $motivo;
        $this->detalle             = $detalle;
        $this->cantidadDemorados   = $cantidadDemorados;
        $this->maxDemora           = $maxDemora;
        $this->numero_turno        = $numero_turno;
        $this->paciente_urgencias  = $paciente_urgencias;
        $this->tiempoFormateado    = $tiempoFormateado;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Alerta Demora Medico - ' . $this->motivo,
            from: new Address(
                env('MAIL_FROM_ADDRESS'),
                env('MAIL_FROM_NAME')
            )
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alerta-medico',
            with: [
                'motivo'             => $this->motivo,
                'detalle'            => $this->detalle,
                'cantidadDemorados'  => $this->cantidadDemorados,
                'maxDemora'          => $this->maxDemora,
                'numero_turno'       => $this->numero_turno,
                'paciente_urgencias' => $this->paciente_urgencias,
                'tiempoFormateado'   => $this->tiempoFormateado, // ← agregar
            ]
        );
    }
}
