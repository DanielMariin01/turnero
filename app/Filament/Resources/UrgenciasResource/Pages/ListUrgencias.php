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
use Illuminate\Support\Facades\Log;

class ListUrgencias extends ListRecords
{
    protected static string $resource = UrgenciasResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->verificarAlertaTriage();
    }

    public function getPollingInterval(): ?string
    {
        return '60s';
    }

    public function poll(): void
    {
        $this->verificarAlertaTriage();
    }

    public function verificarAlertaTriage(): void
    {
        Log::info('Verificando alerta triage...');

        // Evita enviar correo repetido: solo 1 vez cada 15 minutos
        if (Cache::has('alerta_triage_enviada')) {
            Log::info('Alerta ya enviada recientemente, omitiendo...');
            return;
        }

        $pacientesEnEspera = Turno::hoy()
            ->where('motivo', 'urgencias')
            ->where('estado', 'en_espera')
            ->whereNull('hora_llamado')
            ->get();

        $cantidad = $pacientesEnEspera->count();

        $maxEspera = $pacientesEnEspera->max(
            fn($t) => Carbon::parse($t->hora)->diffInMinutes(now())
        ) ?? 0;

        Log::info("Pacientes en espera: {$cantidad}, Tiempo máximo: {$maxEspera} min");

        // ¿Se cumple alguna condición?
        if ($cantidad < 3 && $maxEspera < 10) {
            Log::info('No se cumple ninguna condición de alerta.');
            return;
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
            $detalles[] = "Un paciente lleva {$maxEspera} min sin clasificar (límite: 10 min).";
        }

        $motivo  = implode(' | ', $motivos);
        $detalle = implode(' ', $detalles);

        // Enviar correo a los 3 destinatarios
        $correos = array_filter([
            env('ALERTA_TRIAGE_CORREO_1'),
            env('ALERTA_TRIAGE_CORREO_2'),
            env('ALERTA_TRIAGE_CORREO_3'),
        ]);

        Log::info('Enviando alerta a: ' . implode(', ', $correos));

        $numerosTurnos = $pacientesEnEspera->pluck('numero_turno')->join(', ');

        foreach ($correos as $correo) {
            Mail::to($correo)->send(new AlertaTriageMail(
                $motivo,
                $detalle,
                $cantidad,
                $maxEspera,
                $numerosTurnos
            ));
        }

        // Bloquear 15 minutos para no repetir el envío
        Cache::put('alerta_triage_enviada', true, now()->addMinutes(15));

        // Aviso visual en pantalla
        Notification::make()
            ->title('📧 Alerta enviada')
            ->body('Se notificó por correo a los responsables.')
            ->warning()
            ->persistent()
            ->send();

        Log::info('Alerta enviada correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
