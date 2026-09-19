<?php

namespace App\Console\Commands;

use App\Models\Turno;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SincronizarTriageUrgencias extends Command
{
    protected $signature = 'urgencias:sincronizar-triage';
    protected $description = 'Vigila la clasificación de Triage en la clínica y enruta los turnos de Urgencias automáticamente.';

    const INTERVALO_SEGUNDOS = 5;
    const MEMORIA_MAXIMA_MB = 128; // reinicio preventivo solo si la memoria crece de más

    // Solo se vigilan turnos en estos estados
    const ESTADOS_A_VIGILAR = ['asignado', 'llamado_medico'];

    // Solo se revisan turnos creados en las últimas N horas
    const VENTANA_HORAS = 48;

    public function handle(): int
    {
        $log = Log::channel('urgencias');

        $log->info('Urgencias: vigilante de Triage iniciado');
        $this->info('Vigilante de Triage iniciado. Presiona Ctrl+C para detener.');

        while (true) {
            try {
                $this->procesarPendientes($log);
            } catch (\Throwable $e) {
                $log->critical('Urgencias: error inesperado en el vigilante de Triage', [
                    'error' => $e->getMessage(),
                    'archivo' => $e->getFile() . ':' . $e->getLine(),
                ]);
                $this->purgarConexiones();
            }

            if ($this->debeReiniciar()) {
                $log->info('Urgencias: reinicio preventivo del vigilante de Triage por memoria');
                return self::FAILURE;   // ⬅️ para que Windows lo detecte como "falla" y lo reinicie solo
            }

            sleep(self::INTERVALO_SEGUNDOS);
        }
    }

    private function debeReiniciar(): bool
    {
        $memoriaMb = memory_get_usage(true) / 1024 / 1024;

        return $memoriaMb > self::MEMORIA_MAXIMA_MB;
    }

    private function purgarConexiones(): void
    {
        DB::purge('sqlsrv');
        DB::purge(); // conexión por defecto (donde vive Turno)
    }

    private function procesarPendientes($log): void
    {
        $pendientes = Turno::with('paciente')
            ->where('motivo', 'urgencias')
            ->whereIn('estado', self::ESTADOS_A_VIGILAR)
            ->whereNull('nivel_triage')
            ->whereNotNull('ingreso_consecutivo')
            ->where('created_at', '>=', now()->subHours(self::VENTANA_HORAS))
            ->get();

        foreach ($pendientes as $turno) {
            try {
                $this->procesarTurno($turno, $log);
            } catch (\Throwable $e) {
                $log->error('Urgencias: error procesando turno', [
                    'id_turno' => $turno->id_turno,
                    'error' => $e->getMessage(),
                    'archivo' => $e->getFile() . ':' . $e->getLine(),
                ]);
                $this->purgarConexiones();
            }
        }
    }

    private function procesarTurno(Turno $turno, $log): void
    {
        $documento = $turno->paciente->numero_documento ?? null;
        $tipoDocumento = $turno->paciente->tipo_documento ?? null;

        if (!$documento || !$tipoDocumento) {
            return;
        }

        $resultado = DB::connection('sqlsrv')->table('HCCOM1')
            ->where('HISCKEY', $documento)
            ->where('HISTipDoc', $tipoDocumento)
            ->where('HCtvIn1', $turno->ingreso_consecutivo)
            ->where('HISCLTR', '>', 0)
            ->value('HISCLTR');

        if ($resultado === null) {
            return;
        }

        $nivel = (int) $resultado;
        $refId = (string) Str::uuid();

        $log->info('Urgencias: nivel de Triage detectado', [
            'ref' => $refId,
            'id_turno' => $turno->id_turno,
            'numero_turno' => $turno->numero_turno,
            'estado_previo' => $turno->estado,
            'nivel_triage' => $nivel,
        ]);

        $datos = ['nivel_triage' => $nivel];

        switch ($nivel) {
            case 1:
            case 2:
            case 3:
                $datos['estado'] = 'en_proceso';
                $datos['estado_admisiones'] = 'pendiente';
                $datos['hora_ingreso_cola'] = now()->format('H:i:s');
                break;
            case 4:
            case 5:
                $datos['estado'] = 'atendido';
                $datos['hora_finalizacion'] = now()->format('H:i:s');
                break;
            default:
                $log->warning('Urgencias: nivel de Triage fuera de rango (1-5)', [
                    'ref' => $refId,
                    'id_turno' => $turno->id_turno,
                    'nivel_triage' => $nivel,
                ]);
        }

        // Actualización atómica: solo si el turno sigue en un estado vigilado y sin Triage
        $afectados = Turno::where('id_turno', $turno->id_turno)
            ->whereNull('nivel_triage')
            ->whereIn('estado', self::ESTADOS_A_VIGILAR)
            ->update($datos);

        if ($afectados === 0) {
            $log->info('Urgencias: turno ya procesado o cambió de estado', [
                'ref' => $refId,
                'id_turno' => $turno->id_turno,
            ]);
            return;
        }

        $log->info('Urgencias: turno enrutado según Triage', [
            'ref' => $refId,
            'id_turno' => $turno->id_turno,
            'nivel_triage' => $nivel,
            'cambios' => $datos,
        ]);
    }
}
