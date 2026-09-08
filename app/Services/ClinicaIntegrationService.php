<?php

namespace App\Services;

use App\Models\Paciente;
use App\Models\Turno;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use RuntimeException;

class ClinicaIntegrationService
{
    /** Código fijo para "Triage" en el catálogo ClaPro de la clínica */
    const CLAPRO_TRIAGE = '5';

    /**
     * Orquesta la creación completa de un turno de Urgencias:
     * paciente + ingreso en la clínica, y paciente + turno local.
     * Si cualquier paso falla, deshace todo lo que ya se había creado.
     *
     * @throws RuntimeException si no se pudo completar el proceso
     */
    public function crearTurnoUrgencias(array $datos): Turno
    {
        $documento = $datos['numero_documento'];
        $tipoDocumento = $datos['tipo_documento'];

        $pacienteCreadoEnClinica = false;
        $ingCsc = null;

        try {
            $pacienteCreadoEnClinica = $this->buscarOCrearPacienteClinica(
                $documento,
                $tipoDocumento,
                $datos['nombre'],
                $datos['apellido']
            );

            $ingCsc = $this->crearIngresoClinica($documento, $tipoDocumento, $datos['contrato_nit']);

            $turno = DB::transaction(function () use ($datos, $documento, $ingCsc) {
                Log::info('Urgencias: creando paciente local', ['documento' => $documento]);

                $paciente = Paciente::firstOrCreate(
                    ['numero_documento' => $documento],
                    [
                        'nombre' => $datos['nombre'],
                        'apellido' => $datos['apellido'],
                        'tipo_documento' => $datos['tipo_documento'],
                    ]
                );

                $numeroTurno = $this->generarNumeroTurno();
                $fecha = Carbon::today()->toDateString();
                $hora = Carbon::now()->toTimeString();

                $turno = Turno::create([
                    'fk_paciente' => $paciente->id_paciente,
                    'numero_turno' => $numeroTurno,
                    'motivo' => 'urgencias',
                    'fecha' => $fecha,
                    'hora' => $hora,
                    'estado' => 'asignado',
                    'hora_atendido' => $hora,
                    'paciente_urgencias' => trim($paciente->nombre . ' ' . $paciente->apellido),
                    'contrato_nit' => $datos['contrato_nit'],
                    'contrato_nombre' => $datos['contrato_nombre'],
                    'ingreso_consecutivo' => $ingCsc,
                ]);

                Log::info('Urgencias: turno local creado', [
                    'id_turno' => $turno->id_turno,
                    'numero_turno' => $turno->numero_turno,
                ]);

                return $turno;
            });

            $turno->load('paciente');

            Log::info('Urgencias: turno completo creado exitosamente', [
                'id_turno' => $turno->id_turno,
                'documento' => $documento,
                'ingreso_consecutivo' => $ingCsc,
            ]);

            return $turno;
        } catch (\Throwable $e) {
            Log::error('Urgencias: fallo creando turno, deshaciendo cambios', [
                'documento' => $documento,
                'error' => $e->getMessage(),
            ]);

            if ($ingCsc !== null) {
                $this->eliminarIngresoClinica($ingCsc);
            }
            if ($pacienteCreadoEnClinica) {
                $this->eliminarPacienteClinica($documento, $tipoDocumento);
            }

            throw new RuntimeException('No se pudo generar el turno de urgencias: ' . $e->getMessage(), 0, $e);
        }
    }

    private function buscarOCrearPacienteClinica(string $documento, string $tipoDocumento, string $nombreCompleto, string $apellidoCompleto): bool
    {
        Log::info('Urgencias: verificando paciente en clínica (CAPBAS)', ['documento' => $documento]);

        try {
            $existe = DB::connection('sqlsrv')->table('CAPBAS')
                ->where('MPCedu', $documento)
                ->where('MPTDoc', $tipoDocumento)
                ->exists();

            if ($existe) {
                Log::info('Urgencias: paciente ya existía en CAPBAS', ['documento' => $documento]);
                return false;
            }

            [$nombre1, $nombre2] = $this->separarEnDosPartes($nombreCompleto);
            [$apellido1, $apellido2] = $this->separarEnDosPartes($apellidoCompleto);
            $nombreConcatenado = trim("$nombre1 $nombre2 $apellido1 $apellido2");

            DB::connection('sqlsrv')->table('CAPBAS')->insert([
                'MPCedu' => $documento,
                'MPTDoc' => $tipoDocumento,
                'MPNom1' => $nombre1,
                'MPNom2' => $nombre2,
                'MPApe1' => $apellido1,
                'MPApe2' => $apellido2,
                'MPNOMC' => $nombreConcatenado,
                'MPEstPac' => 'S',
            ]);

            Log::info('Urgencias: paciente creado en CAPBAS', ['documento' => $documento]);
            return true;
        } catch (\Throwable $e) {
            Log::error('Urgencias: error creando paciente en CAPBAS', [
                'documento' => $documento,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function crearIngresoClinica(string $documento, string $tipoDocumento, string $contratoNit): int
    {
        Log::info('Urgencias: creando ingreso en clínica (INGRESOS)', ['documento' => $documento]);

        try {
            $ingCsc = DB::connection('sqlsrv')->table('INGRESOS')->insertGetId([
                'MPCedu' => $documento,
                'MPTDoc' => $tipoDocumento,
                'ClaPro' => self::CLAPRO_TRIAGE,
                'IngFecAdm' => now(),
                'IngNit' => $contratoNit,
            ], 'IngCsc');

            Log::info('Urgencias: ingreso creado en clínica', [
                'documento' => $documento,
                'ing_csc' => $ingCsc,
            ]);

            return $ingCsc;
        } catch (\Throwable $e) {
            Log::error('Urgencias: error creando ingreso en INGRESOS', [
                'documento' => $documento,
                'contrato_nit' => $contratoNit,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function eliminarIngresoClinica(int $ingCsc): void
    {
        try {
            DB::connection('sqlsrv')->table('INGRESOS')->where('IngCsc', $ingCsc)->delete();
            Log::warning('Urgencias: ingreso revertido en la clínica', ['ing_csc' => $ingCsc]);
        } catch (\Throwable $e) {
            Log::critical('Urgencias: NO se pudo revertir el ingreso en la clínica, requiere revisión manual', [
                'ing_csc' => $ingCsc,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function eliminarPacienteClinica(string $documento, string $tipoDocumento): void
    {
        try {
            DB::connection('sqlsrv')->table('CAPBAS')
                ->where('MPCedu', $documento)
                ->where('MPTDoc', $tipoDocumento)
                ->delete();
            Log::warning('Urgencias: paciente revertido en la clínica', ['documento' => $documento]);
        } catch (\Throwable $e) {
            Log::critical('Urgencias: NO se pudo revertir el paciente en la clínica, requiere revisión manual', [
                'documento' => $documento,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function generarNumeroTurno(): string
    {
        $fecha = Carbon::today()->toDateString();

        $ultimoTurno = Turno::whereDate('fecha', $fecha)
            ->orderBy('id_turno', 'desc')
            ->first();

        if ($ultimoTurno) {
            preg_match('/\d+$/', $ultimoTurno->numero_turno, $matches);
            $numero = (isset($matches[0]) ? intval($matches[0]) : 0) + 1;
        } else {
            $numero = 1;
        }

        return 'UR' . $numero;
    }

    private function separarEnDosPartes(string $texto): array
    {
        $partes = explode(' ', trim($texto), 2);
        return [$partes[0] ?? '', $partes[1] ?? ''];
    }
}
