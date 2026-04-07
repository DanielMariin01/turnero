<?php

namespace App\Filament\Resources\UrgenciasResource\Pages;

use App\Filament\Resources\UrgenciasResource;
use App\Mail\AlertaTriageMail;
use App\Models\Turno;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class ListUrgencias extends ListRecords
{
    protected static string $resource = UrgenciasResource::class;

    protected function getTablePollingInterval(): ?string
    {
        $this->verificarAlertaTriage();
        return '60s';
    }

    public function verificarAlertaTriage(): void
    {
        // Evita enviar correo repetido: solo 1 vez cada 15 minutos
        //if (Cache::has('alerta_triage_enviada')) {
           // return;
       //}

        $pacientesEnEspera = Turno::hoy()
            ->where('motivo', 'urgencias')
            ->where('estado', 'en_espera')
            ->whereNull('hora')
            ->get();

        $cantidad = $pacientesEnEspera->count();

        $maxEspera = $pacientesEnEspera->max(
            fn($t) => Carbon::parse($t->hora)->diffInMinutes(now())
        ) ?? 0;

        // ¿Se cumple alguna condición?
        if ($cantidad < 3 && $maxEspera < 10) {
            return; // Todo normal, no hace nada
        }

        // Armar mensaje del correo
        $motivos  = [];
        $detalles = [];

        if ($cantidad >= 3) {
            $motivos[]  = 'Alta demanda';
            $detalles[] = "{$cantidad} pacientes esperando triage (límite: 3).";
        }

        if ($maxEspera >= 10) {
            $motivos[]  = 'Tiempo excedido';
            $detalles[] = "Un paciente lleva {$maxEspera} min sin ser atendido (límite: 10 min).";
        }

        // Enviar correo a los 3 destinatarios
        $correos = array_filter([
            env('ALERTA_TRIAGE_CORREO_1'),
            env('ALERTA_TRIAGE_CORREO_2'),
            env('ALERTA_TRIAGE_CORREO_3'),
        ]);

        foreach ($correos as $correo) {
            Mail::to($correo)->send(new AlertaTriageMail(
                implode(' | ', $motivos),
                implode(' ', $detalles),
                $cantidad,
                $maxEspera
            ));
        }

        // Bloquear 15 minutos para no repetir el envío
        //Cache::put('alerta_triage_enviada', true, now()->addMinutes(15));

        // Aviso visual en pantalla también
        Notification::make()
            ->title('📧 Alerta enviada')
            ->body('Se notificó por correo a los responsables.')
            ->warning()
            ->persistent()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
