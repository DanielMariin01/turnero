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
    public string $tiempoFormateado;

    public function __construct($motivo, $detalle, $cantidadEspera, $maxEspera, $numero_turno)
    {
        $this->motivo = $motivo;
        $this->detalle = $detalle;
        $this->cantidadEspera = $cantidadEspera;
        $this->maxEspera = $maxEspera;
        $this->numero_turno = $numero_turno;
        $this->tiempoFormateado = $this->formatearTiempo($maxEspera);
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
            view: 'emails.alerta-triage',
        );
    }
    private function formatearTiempo($minutos)
    {
        $horas = intdiv($minutos, 60);
        $minutosRestantes = $minutos % 60;

        if ($horas > 0 && $minutosRestantes > 0) {
            return "{$horas} hora" . ($horas > 1 ? 's' : '') . " y {$minutosRestantes} minuto" . ($minutosRestantes > 1 ? 's' : '');
        }

        if ($horas > 0) {
            return "{$horas} hora" . ($horas > 1 ? 's' : '');
        }

        return "{$minutosRestantes} minuto" . ($minutosRestantes > 1 ? 's' : '');
    }
}
