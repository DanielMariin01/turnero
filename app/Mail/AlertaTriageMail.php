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
    public string $numero_turno;

     public function __construct($motivo, $detalle, $cantidadEspera, $maxEspera, $numero_turno)
    {
        $this->motivo = $motivo;
        $this->detalle = $detalle;
        $this->cantidadEspera = $cantidadEspera;
        $this->maxEspera = $maxEspera;
        $this->numero_turno = $numero_turno;
    }

    public function build()
    {
        return $this->subject('Alerta Triage Urgencias - ' . $this->motivo)
                    ->view('emails.alerta-triage');
    }

 

    
}
