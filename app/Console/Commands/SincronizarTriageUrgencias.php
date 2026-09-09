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

    public function handle()
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
            }

            sleep(self::INTERVALO_SEGUNDOS);
        }
    }

    private function procesarPendientes($log): void
    {
        $pendientes = Turno::with('paciente')
            ->where('motivo', 'urgencias')
            ->where('estado', 'llamado_medico')
            ->whereNull('nivel_triage')
            ->whereNotNull('ingreso_consecutivo')
            ->get();

        foreach ($pendientes as $turno) {
            $this->procesarTurno($turno, $log);
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
            'nivel_triage' => $nivel,
        ]);

        $datos = ['nivel_triage' => $nivel];

        switch ($nivel) {
            case 1:
                $datos['estado'] = 'en_proceso';
                $datos['estado_admisiones'] = 'pendiente';
                $datos['estado_consulta_medica'] = 'pendiente';
                $datos['hora_ingreso_cola'] = now()->format('H:i:s');
                break;
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

        $turno->update($datos);

        $log->info('Urgencias: turno enrutado según Triage', [
            'ref' => $refId,
            'id_turno' => $turno->id_turno,
            'nivel_triage' => $nivel,
            'cambios' => $datos,
        ]);
    }
}
