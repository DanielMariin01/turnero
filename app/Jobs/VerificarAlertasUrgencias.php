<?php

namespace App\Jobs;

use App\Mail\AlertaTriageMail;
use App\Mail\AlertaMedicoMail;
use App\Models\Turno;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class VerificarAlertasUrgencias implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $this->verificarTriage();
        $this->verificarMedico();
    }

    private function verificarTriage(): void
    {
        if (Cache::has('alerta_triage_enviada')) {
            Log::info('Triage: cache activo, omitiendo.');
            return;
        }

        $pacientes = Turno::hoy()
            ->where('motivo', 'urgencias')
            ->where('estado', 'en_espera')
            ->whereNull('hora_llamado')
            ->get();

        $cantidad  = $pacientes->count();
        $maxEspera = (int) round($pacientes->max(
            fn($t) => Carbon::parse($t->hora)->diffInMinutes(now())
        ) ?? 0);

        Log::info("Triage: cantidad={$cantidad}, maxEspera={$maxEspera}");

        if ($cantidad < 3 && $maxEspera < 10) {
            Log::info('Triage: no cumple condiciones.');
            return;
        }

        $motivos = $detalles = [];
        if ($cantidad >= 3) {
            $motivos[]  = 'Alta demanda';
            $detalles[] = "{$cantidad} pacientes esperando triage (límite: 3).";
        }
        if ($maxEspera >= 10) {
            $motivos[]  = 'Tiempo excedido';
            $detalles[] = "Un paciente lleva {$maxEspera} min sin clasificar (límite: 10 min).";
        }

        $correos = array_filter([
            env('ALERTA_TRIAGE_CORREO_4'),
        ]);

        Log::info('Triage: correos destino: ' . implode(', ', $correos));

        $numerosTurnos = $pacientes->pluck('numero_turno')->join(', ');

        foreach ($correos as $correo) {
            try {
                Mail::to($correo)->send(new AlertaTriageMail(
                    implode(' | ', $motivos),
                    implode(' ', $detalles),
                    $cantidad,
                    $maxEspera,
                    $numerosTurnos
                ));
                Log::info("Triage: correo enviado a {$correo}");
            } catch (\Exception $e) {
                Log::error("Triage: error enviando a {$correo}: " . $e->getMessage());
            }
        }

        Cache::put('alerta_triage_enviada', true, now()->addMinutes(2));
        Log::info('Alerta triage enviada desde job.');
    }

    private function verificarMedico(): void
    {
        if (Cache::has('alerta_medico_enviada')) return;

        $pacientesAsignados = Turno::hoy()
            ->where('motivo', 'urgencias')
            ->where('estado', 'asignado')
            ->whereNotNull('hora_atendido')
            ->get();

        $demorados = $pacientesAsignados->filter(
            fn($t) => Carbon::parse($t->hora_atendido)->diffInMinutes(now()) >= 15
        );

        if ($demorados->isEmpty()) return;

        $maxDemora = (int) round($demorados->max(
            fn($t) => Carbon::parse($t->hora_atendido)->diffInMinutes(now())
        ));

        $correos = array_filter([

            env('ALERTA_TRIAGE_CORREO_4'),
        ]);

        foreach ($correos as $correo) {
            Mail::to($correo)->send(new AlertaMedicoMail(
                'Demora del medico',
                "Turnos sin atención: {$demorados->pluck('numero_turno')->join(', ')}.",
                $demorados->count(),
                $maxDemora,
                $demorados->pluck('numero_turno')->join(', '),
                $demorados->pluck('paciente_urgencias')->join(', '),
                $this->formatearTiempo($maxDemora)
            ));
        }

        Cache::put('alerta_medico_enviada', true, now()->addMinutes(2));
        Log::info('Alerta médico enviada desde job.');
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
