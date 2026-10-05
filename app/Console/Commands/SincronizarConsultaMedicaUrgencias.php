<?php

namespace App\Console\Commands;

use App\Models\Turno;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SincronizarConsultaMedicaUrgencias extends Command
{
    protected $signature = 'urgencias:sincronizar-consulta-medica';
    protected $description = 'Vigila si la clínica ya registró atención médica y cierra el turno automáticamente.';

    const INTERVALO_SEGUNDOS = 5;
    const MEMORIA_MAXIMA_MB = 128;
    const VENTANA_HORAS = 48;

    // true = solo escribe en el log, NO cierra ningún turno. Cámbialo a false cuando la prueba salga bien.
    const SOLO_PROBAR = false;

    private array $yaRegistrados = [];

    public function handle(): int
    {
        $log = Log::channel('urgencias');
        $log->info('Urgencias: vigilante de Consulta Médica iniciado', ['solo_probar' => self::SOLO_PROBAR]);
        $this->info('Vigilante de Consulta Médica iniciado. Presiona Ctrl+C para detener.');

        while (true) {
            try {
                $this->procesarPendientes($log);
            } catch (\Throwable $e) {
                $log->critical('Urgencias: error inesperado en el vigilante de Consulta Médica', [
                    'error' => $e->getMessage(),
                    'archivo' => $e->getFile() . ':' . $e->getLine(),
                ]);
                $this->purgarConexiones();
            }

            if ($this->debeReiniciar()) {
                $log->info('Urgencias: reinicio preventivo del vigilante de Consulta Médica por memoria');
                return self::FAILURE;
            }

            sleep(self::INTERVALO_SEGUNDOS);
        }
    }

    private function debeReiniciar(): bool
    {
        return (memory_get_usage(true) / 1024 / 1024) > self::MEMORIA_MAXIMA_MB;
    }

    private function purgarConexiones(): void
    {
        DB::purge('sqlsrv');
        DB::purge();
    }

    private function procesarPendientes($log): void
    {
        $pendientes = Turno::with('paciente')
            ->where('motivo', 'urgencias')
            ->whereIn('nivel_triage', [1, 2, 3])
            ->whereNotIn('estado', ['atendido', 'no_atendido'])
            ->whereNotNull('ingreso_consecutivo')
            ->where('created_at', '>=', now()->subHours(self::VENTANA_HORAS))
            ->get();

        foreach ($pendientes as $turno) {
            try {
                $this->procesarTurno($turno, $log);
            } catch (\Throwable $e) {
                $log->error('Urgencias: error procesando turno (Consulta Médica)', [
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

        // Solo SELECT en SQL Server. La comparación de fechas ocurre dentro de SQL.
        // Se exige al menos 1 segundo de diferencia con el Triage para no tomar
        // la propia fila del Triage como si fuera la atención médica.
        $fila = DB::connection('sqlsrv')->selectOne("
            SELECT TOP 1 H2.HISCFCON AS fecha_atencion, T.HISFSAL AS fin_triage
            FROM HCCOM1 T WITH (NOLOCK)
            INNER JOIN HCCOM1 H2 WITH (NOLOCK)
                ON  H2.HISCKEY   = T.HISCKEY
                AND H2.HISTipDoc = T.HISTipDoc
                AND H2.HCtvIn1   = T.HCtvIn1
                AND H2.HISCFCON  > DATEADD(SECOND, 1, T.HISFSAL)
            WHERE T.HISCKEY   = ?
              AND T.HISTipDoc = ?
              AND T.HCtvIn1   = ?
              AND T.HISCLTR   > 0
            ORDER BY H2.HISCFCON ASC
        ", [$documento, $tipoDocumento, $turno->ingreso_consecutivo]);

        if ($fila === null || (string) $fila->fecha_atencion === (string) $fila->fin_triage) {
            return; // sin Triage, o la clínica todavía no registra atención médica
        }

        $refId = (string) Str::uuid();

        if (self::SOLO_PROBAR) {
            if (!isset($this->yaRegistrados[$turno->id_turno])) {
                $this->yaRegistrados[$turno->id_turno] = true;
                $log->info('PRUEBA: se cerraría este turno (no se modificó nada)', [
                    'id_turno' => $turno->id_turno,
                    'numero_turno' => $turno->numero_turno,
                    'documento' => $documento,
                    'fin_triage' => $fila->fin_triage,
                    'fecha_atencion_medica' => $fila->fecha_atencion,
                ]);
            }
            return;
        }

        $log->info('Urgencias: consulta médica detectada como finalizada en la clínica', [
            'ref' => $refId,
            'id_turno' => $turno->id_turno,
            'numero_turno' => $turno->numero_turno,
            'fin_triage' => $fila->fin_triage,
            'fecha_atencion_medica' => $fila->fecha_atencion,
        ]);

        // Esto solo escribe en tu MySQL local (tabla turno)
        $afectados = Turno::where('id_turno', $turno->id_turno)
            ->whereNotIn('estado', ['atendido', 'no_atendido'])
            ->update([
                'estado' => 'atendido',
                'estado_admisiones' => 'atendido',
                'estado_consulta_medica' => 'atendido',
                'hora_finalizacion' => now()->format('H:i:s'),
            ]);

        if ($afectados === 0) {
            $log->info('Urgencias: turno ya estaba cerrado (Consulta Médica)', [
                'ref' => $refId,
                'id_turno' => $turno->id_turno,
            ]);
            return;
        }

        $log->info('Urgencias: turno cerrado automáticamente por fin de consulta médica en la clínica', [
            'ref' => $refId,
            'id_turno' => $turno->id_turno,
        ]);
    }
}
