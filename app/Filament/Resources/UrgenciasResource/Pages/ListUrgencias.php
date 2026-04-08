<?php

namespace App\Filament\Resources\UrgenciasResource\Pages;

use App\Filament\Resources\UrgenciasResource;
use App\Mail\AlertaTriageMail;
use App\Mail\AlertaMedicoMail;
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
        $this->verificarAlertaMedico();
    }

    public function getPollingInterval(): ?string
    {
        return '60s';
    }

    public function poll(): void
    {
        $this->verificarAlertaTriage();
        $this->verificarAlertaMedico();
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

        // Bloquear 3 minutos para no repetir el envío
        Cache::put('alerta_triage_enviada', true, now()->addMinutes(3));

        // Aviso visual en pantalla
        Notification::make()
            ->title('📧 Alerta enviada')
            ->body('Se notificó por correo a los responsables.')
            ->warning()
            ->persistent()
            ->send();

        Log::info('Alerta enviada correctamente.');
    }

    //ALERTA CUANDO EL MEDICO EXCEDIO EL LIMNITE DE PACIENTES EN LISTA O EL LIMITE DE TIEMPO EN LLAMAR A UN PACIENTE

    public function verificarAlertaMedico(): void
    {
        Log::info('Verificando alerta medico...');

        if (Cache::has('alerta_medico_enviada')) {
            Log::info('Alerta medico ya enviada recientemente, omitiendo...');
            return;
        }

        $pacientesAsignados = Turno::hoy()
            ->where('motivo', 'urgencias')
            ->where('estado', 'asignado')
            ->whereNotNull('hora_atendido')
            ->get();

        $pacientesDemorados = $pacientesAsignados->filter(function ($turno) {
            return Carbon::parse($turno->hora_atendido)->diffInMinutes(now()) >= 15;
        });

        if ($pacientesDemorados->isEmpty()) {
            Log::info('No hay medicos demorados.');
            return;
        }

        $numerosTurnos      = $pacientesDemorados->pluck('numero_turno')->join(', ');
        $pacientesNombres   = $pacientesDemorados->pluck('paciente_urgencias')->join(', ');
        $maxDemora          = $pacientesDemorados->max(
            fn($t) => Carbon::parse($t->hora_atendido)->diffInMinutes(now())
        );

        $motivo  = 'Demora del medico';
        $detalle = "Los siguientes turnos llevan mas de 15 min sin atencion: {$numerosTurnos}.";

        $correos = array_filter([
            env('ALERTA_TRIAGE_CORREO_1'),
            env('ALERTA_TRIAGE_CORREO_2'),
            env('ALERTA_TRIAGE_CORREO_3'),
        ]);
        $tiempoFormateado = $this->formatearTiempo($maxDemora);
        foreach ($correos as $correo) {
            Mail::to($correo)->send(new AlertaMedicoMail(
                $motivo,
                $detalle,
                $pacientesDemorados->count(),
                $maxDemora,
                $numerosTurnos,
                $pacientesNombres,
                $tiempoFormateado
            ));
        }

        Cache::put('alerta_medico_enviada', true, now()->addMinutes(3));

        Notification::make()
            ->title('📧 Alerta medico enviada')
            ->body('Se notifico por correo sobre demora del medico.')
            ->danger()
            ->persistent()
            ->send();

        Log::info('Alerta medico enviada correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

     private function formatearTiempo(int $minutos): string
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
