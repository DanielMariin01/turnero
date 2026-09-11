<?php

namespace App\Services;

use App\Models\Paciente;
use App\Models\Turno;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use RuntimeException;

class ClinicaIntegrationService
{
    const CLAPRO_TRIAGE = '5';
    const PABELLON_TRIAGE = 15;
    const USUARIO_SISTEMA = 'SISTEMAS';

    /**
     * Orquesta la creación completa de un turno de Urgencias:
     * paciente + ingreso (INGRESOS+INGRESOMP+LOGINGR) en la clínica,
     * y paciente + turno local. Si algo falla, deshace todo lo creado.
     */
    public function crearTurnoUrgencias(array $datos): Turno
    {
        $documento = $datos['numero_documento'];
        $tipoDocumento = $datos['tipo_documento'];

        // Código de referencia único para rastrear este intento en todos los logs
        $refId = (string) Str::uuid();
        $inicio = microtime(true);

        $ingCsc = null;
        $etapa = null;

        $log = Log::channel('urgencias');

        $log->info('Urgencias: INICIO de creación de turno', [
            'ref' => $refId,
            'documento' => $documento,
            'tipo_documento' => $tipoDocumento,
            'contrato_nit' => $datos['contrato_nit'] ?? null,
        ]);


        try {
            $etapa = 'desactivar_ingresos_previos';
            $this->desactivarIngresosActivosClinica($documento, $tipoDocumento, $refId);
            $etapa = 'paciente_clinica';

            $this->buscarOCrearPacienteClinica(
                $documento,
                $tipoDocumento,
                $datos['nombre'],
                $datos['apellido'],
                $datos['fecha_nacimiento'],
                $datos['sexo'],

                $refId
            );

            $etapa = 'afiliacion_clinica';
            $this->buscarOCrearAfiliacionClinica($documento, $tipoDocumento, $datos['contrato_nit'], $refId);

            $etapa = 'ingreso_clinica';
            $ingCsc = $this->crearIngresoCompletoClinica(
                $documento,
                $tipoDocumento,
                $datos['contrato_nit'],
                $refId
            );

            $etapa = 'turno_local';
            $turno = DB::transaction(function () use ($datos, $documento, $ingCsc, $refId, $log) {
                $log->info('Urgencias: creando paciente local', ['ref' => $refId, 'documento' => $documento]);

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

                $log->info('Urgencias: turno local creado', [
                    'ref' => $refId,
                    'id_turno' => $turno->id_turno,
                    'numero_turno' => $turno->numero_turno,
                ]);

                return $turno;
            });

            $turno->load('paciente');

            $duracionMs = round((microtime(true) - $inicio) * 1000);

            $log->info('Urgencias: ÉXITO - turno completo creado', [
                'ref' => $refId,
                'id_turno' => $turno->id_turno,
                'numero_turno' => $turno->numero_turno,
                'documento' => $documento,
                'ingreso_consecutivo' => $ingCsc,
                'duracion_ms' => $duracionMs,
            ]);

            return $turno;
        } catch (\Throwable $e) {
            $duracionMs = round((microtime(true) - $inicio) * 1000);

            $log->error('Urgencias: FALLO creando turno, deshaciendo cambios', [
                'ref' => $refId,
                'etapa' => $etapa,
                'documento' => $documento,
                'error' => $e->getMessage(),
                'archivo' => $e->getFile() . ':' . $e->getLine(),
                'duracion_ms' => $duracionMs,
            ]);

            if ($ingCsc !== null) {
                $this->eliminarIngresoCompletoClinica($ingCsc, $documento, $tipoDocumento, $refId);
            }

            $mensajesPorEtapa = [
                'desactivar_ingresos_previos' => 'No pudimos preparar su ingreso en el sistema de la clínica.',
                'paciente_clinica' => 'No pudimos verificar sus datos con el sistema de la clínica.',
                'afiliacion_clinica' => 'No pudimos verificar su afiliación con el sistema de la clínica.',
                'ingreso_clinica' => 'No pudimos registrar su ingreso en el sistema de la clínica.',
                'turno_local' => 'No pudimos generar su turno en el sistema.',
            ];
            $mensajeUsuario = $mensajesPorEtapa[$etapa] ?? 'No se pudo generar el turno.';
            $mensajeUsuario .= " Por favor intente de nuevo. (Código de referencia: {$refId})";

            throw new RuntimeException($mensajeUsuario, 0, $e);
        }
    }
    /**
     * Desactiva (EstAdmSld = 'Inactivo') todos los ingresos activos del paciente,
     * sin importar el tipo (ClaPro), para permitir el ingreso de Urgencias.
     * Urgencias nunca puede negar la atención. La reactivación es manual,
     * a cargo del personal de la clínica — este sistema no la revierte.
     * Retorna los IngCsc que fueron desactivados (para dejar rastro claro en el log).
     */
    private function desactivarIngresosActivosClinica(string $documento, string $tipoDocumento, string $refId): array
    {
        $log = Log::channel('urgencias');

        $idsActivos = DB::connection('sqlsrv')->table('INGRESOS')
            ->where('MPCedu', $documento)
            ->where('MPTDoc', $tipoDocumento)
            ->where('IngFecEgr', '<=', '1753-01-02')
            ->where('EstAdmSld', 'Activo')
            ->pluck('IngCsc')
            ->toArray();

        if (empty($idsActivos)) {
            return [];
        }

        DB::connection('sqlsrv')->table('INGRESOS')
            ->where('MPCedu', $documento)
            ->where('MPTDoc', $tipoDocumento)
            ->whereIn('IngCsc', $idsActivos)
            ->update([
                'EstAdmSld' => 'Inactivo',
                'IngFecEgr' => now(),   // ⬅️ agregada
            ]);

        $log->warning('Urgencias: ingresos activos desactivados para permitir turno de Urgencias (reactivación manual requerida por la clínica)', [
            'ref' => $refId,
            'documento' => $documento,
            'ingresos_desactivados' => $idsActivos,
        ]);

        return $idsActivos;
    }
    /**
     * Busca al paciente en CAPBAS; si no existe, lo crea.
     * Retorna true si SE CREÓ un registro nuevo.
     */
    private function buscarOCrearPacienteClinica(
        string $documento,
        string $tipoDocumento,
        string $nombreCompleto,
        string $apellidoCompleto,
        string $fechaNacimiento,
        string $sexo,
        string $refId
    ): bool {
        $log = Log::channel('urgencias');
        $log->info('Urgencias: verificando paciente en clínica (CAPBAS)', ['ref' => $refId, 'documento' => $documento]);

        try {
            $existe = DB::connection('sqlsrv')->table('CAPBAS')
                ->where('MPCedu', $documento)
                ->where('MPTDoc', $tipoDocumento)
                ->exists();

            if ($existe) {
                $log->info('Urgencias: paciente ya existía en CAPBAS', ['ref' => $refId, 'documento' => $documento]);
                return false;
            }

            [$nombre1, $nombre2] = $this->separarEnDosPartes($nombreCompleto);
            [$apellido1, $apellido2] = $this->separarEnDosPartes($apellidoCompleto);
            $nombreConcatenado = trim("$nombre1 $nombre2 $apellido1 $apellido2");

            // Valores genéricos de ubicación (Pereira) — el kiosco no captura dirección real
            $departamentoGenerico = '66';
            $municipioGenerico = 1;
            $barrioGenerico = 80200; // Caminos de Maraya

            DB::connection('sqlsrv')->table('CAPBAS')->insert([
                'MPCedu' => $documento,
                'MPTDoc' => $tipoDocumento,
                'MPNHiC' => $documento,
                'MPNom1' => $nombre1,
                'MPNom2' => $nombre2,
                'MPApe1' => $apellido1,
                'MPApe2' => $apellido2,
                'MPNOMC' => $nombreConcatenado,
                'MPFchN' => $fechaNacimiento,
                'MPSexo' => $sexo,
                'MPDire' => 'NN',
                'MPTele' => '00000',
                'MpTele1' => '00000',
                'MpTele2' => '00000',
                'MDCodD' => $departamentoGenerico,
                'MDCodM' => $municipioGenerico,
                'MDCodB' => $barrioGenerico,
                'MdCodDNac' => $departamentoGenerico,
                'MdCodMNac' => $municipioGenerico,
                'MPEstPac' => 'S',
                'MPGrEs' => '9  ',
                'MPPstNuc' => 0,
                'MPCodEtn' => '06',
                'MPCodDisc' => '1',
                'MPGrPo' => 'ND ',
                'MPConNac' => 0,
                'MPTmpRes' => 0,
                'mpfalta' => 0,
                'MDCodBE' => 0,
                'MPDocInt' => 0,
                'MpCtvoActe' => 0,
                'MpCtvGes' => 0,
                'MpUltCtPr' => 0,
                'MpCtvoAtn' => 0,
                'MpUsrPrf' => '1',
                'MPPacNN' => 'N',
                'MPCtvMed' => 0,
                'MPSemCSis' => 0,
                'MPSmCtCm' => 0,
                'MPTipAfi' => 0,
                'MPCalAfi' => 0,
                'MPBEIps' => 0,
                'MPCodPai' => null,
                'MPFchDef' => '1753-01-01',
                'MPCPEtn' => '',
                'MPCscInM' => 0,
                'MPViveS' => 0,
                'MpOcuAnte' => 0,
                'MPCODSEGTR' => 0,
                'MPCODTRA' => 0,
                'MPConPob' => '',
                'MPINDIS' => '0',
                'MPNivEEs' => 'C',
                'MPTTmRes' => null,
                'MPFECACT' => '1753-01-01',
            ]);

            $log->info('Urgencias: paciente creado en CAPBAS', ['ref' => $refId, 'documento' => $documento]);
            return true;
        } catch (\Throwable $e) {
            $log->error('Urgencias: error creando paciente en CAPBAS', [
                'ref' => $refId,
                'documento' => $documento,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    /**
     * Crea el ingreso completo en la clínica: INGRESOS + INGRESOMP + LOGINGR,
     * dentro de una sola transacción. Retorna el IngCsc generado.
     */
    private function crearIngresoCompletoClinica(string $documento, string $tipoDocumento, string $contratoNit, string $refId): int
    {
        $log = Log::channel('urgencias');
        $log->info('Urgencias: creando ingreso completo en clínica', ['ref' => $refId, 'documento' => $documento]);

        try {
            return DB::connection('sqlsrv')->transaction(function () use ($documento, $tipoDocumento, $contratoNit, $refId, $log) {
                $maxCsc = DB::connection('sqlsrv')->table('INGRESOS')
                    ->where('MPCedu', $documento)
                    ->where('MPTDoc', $tipoDocumento)
                    ->lockForUpdate()
                    ->max('IngCsc');

                $ingCsc = ($maxCsc ?? 0) + 1;

                DB::connection('sqlsrv')->table('INGRESOS')->insert([
                    'MPCedu' => $documento,
                    'MPTDoc' => $tipoDocumento,
                    'ClaPro' => self::CLAPRO_TRIAGE,
                    'IngCsc' => $ingCsc,
                    'IngFecAdm' => now(),
                    'IngUsrReg' => self::USUARIO_SISTEMA,
                    'IngFecEgr' => '1753-01-01',
                    'MPCodP' => self::PABELLON_TRIAGE,
                    'IngNit' => $contratoNit,
                    'IngAtnAct' => self::CLAPRO_TRIAGE,
                    'IngUlcMoP' => 1,
                    'IngTip' => 'GN',
                    'IngCauE' => 13,
                    'EstAdmSld' => 'Activo',
                    'IngDocResp' => '0',
                    'IngTDoResp' => $tipoDocumento,
                    'IngInSlC' => 'N',
                    'IngIPSAtn' => '0',
                    'IngFac' => 0,
                    'IngDoc' => 0,
                    'IngExtEst' => 0,
                    'IngEstSld' => 0,
                    'IngIPS' => 0,
                    'IngDxTip' => 0,
                    'IngMEdEsp' => 0,
                    'IngDxTip1' => 0,
                    'IngDxTip2' => 0,
                    'IngHorObs' => 0,
                    'IngDoAco' => 0,
                    'IngMunRe' => 0,
                    'IngEsMt' => 0,
                    'IngCtvAc' => 0,
                    'IngCodPEg' => 0,
                    'IngNumCit' => 0,
                    'IngCscN' => 0,
                    'IngSege' => 0,
                    'IngUCtvEp' => 0,
                    'IngCnsPlt' => 0,
                    'IngHosTTo' => 0,
                    'IngNroAn1' => 0,
                    'IngNroAn2' => 0,
                    'IngEsSAT' => 0,
                    'IngRieCod' => 0,
                    'IngRiCnDe' => 0,
                    'IngRiCoDe' => 0,
                    'INGDXTIP3' => 0,
                    'indRefac' => 0,
                    'IngFchM' => '1753-01-01',
                    'IngFchAnu' => '1753-01-01',
                    'IngFeHAtU' => '1753-01-01',
                    'IngFSAdTr' => '1900-01-01',
                    'IngFecTur' => '1900-01-01',
                ]);

                DB::connection('sqlsrv')->table('INGRESOMP')->insert([
                    'MPCedu' => $documento,
                    'MPTDoc' => $tipoDocumento,
                    'ClaPro' => self::CLAPRO_TRIAGE,
                    'IngCsc' => $ingCsc,
                    'IngCtvMoP' => 1,
                    'IngCodPab' => self::PABELLON_TRIAGE,
                    'IngCodCam' => '',
                    'IngFecMoP' => now(),
                    'IngUsuMoP' => self::USUARIO_SISTEMA,
                ]);

                DB::connection('sqlsrv')->table('LOGINGR')->insert([
                    'MPCedu' => $documento,
                    'MPTDoc' => $tipoDocumento,
                    'IngCsc' => $ingCsc,
                    'IngFec' => now(),
                    'UsrIng' => self::USUARIO_SISTEMA,
                ]);

                // ⬇️ NUEVO: TMPFAC
                DB::connection('sqlsrv')->table('TMPFAC')->insert([
                    'TFCedu' => $documento,
                    'TFTDoc' => $tipoDocumento,
                    'TmCtvIng' => $ingCsc,   // mismo consecutivo que IngCsc
                    'TFFchI' => now(),
                    'TFHorI' => now()->format('H:i:s'),
                    'SCCCod' => '001',
                    'SccEmp' => '1',
                    'ClaPro' => self::CLAPRO_TRIAGE,

                    'TFMENi' => $contratoNit,
                    'TFcCodPab' => self::PABELLON_TRIAGE,
                    'TFCauE' => 13,
                    'ClaproI' => self::CLAPRO_TRIAGE,
                    'TFTDoRep' => $tipoDocumento,
                    'TFDocRep' => '0',
                    'TFUIng' => self::USUARIO_SISTEMA,
                    'TFTiRe' => '1',

                    'SOFchVIni' => '1900-01-01',
                    'SOFchAcc' => '1900-01-01',
                    'SOFchVFin' => '1900-01-01',
                    'MPFEsH' => '1900-01-01',
                    'TFFchM' => '1753-01-01',
                    'TFFchS' => '1753-01-01',

                    'MICodI' => 0,
                    'SOCodM' => 0,
                    'SOCodMCnd' => 0,
                    'SOTpoEC' => 0,
                    'SOCodME' => 0,
                    'TFViaI' => 0,
                    'TFEsMI' => 0,
                    'TFEsMS' => 0,
                    'TFEstS' => 0,
                    'TFSeGe' => 0,
                    'TFHorO' => 0,
                    'TFVNPU' => 0,
                    'TFUscP' => 0,
                    'TFUlcAC' => 0,
                    'TFEstP' => 0,
                    'TFTotP' => 0,
                    'TFTotS' => 0,
                    'TFTotF' => 0,
                    'TFValS' => 0,
                    'TFVaAb' => 0,
                    'TFVAPU' => 0,
                    'TFVPaU' => 0,
                    'TFVDsc' => 0,
                    'TFUscS' => 0,
                    'TFUcsA' => 0,
                    'TFUcsN' => 0,
                    'TFTpeAut' => 0,
                    'TFVlrAut' => 0,
                    'TFcCodCns' => 0,
                    'TFVlrImpt' => 0,
                    'TFCnTQx' => 0,
                    'ReFatMat' => 0,
                    'ReFatNum' => 0,
                    'TmCtvAct' => 0,
                    'TFIPSENT' => 0,
                    'TFDocAco' => 0,
                    'TFMunRes' => 0,
                    'TFEsMt' => 0,
                    'TFVPOCo' => 0,
                    'TFVlrTIv' => 0,
                ]);

                $log->info('Urgencias: ingreso completo creado (INGRESOS+INGRESOMP+LOGINGR+TMPFAC)', [
                    'ref' => $refId,
                    'documento' => $documento,
                    'ing_csc' => $ingCsc,
                ]);

                return $ingCsc;
            });
        } catch (\Throwable $e) {
            $log->error('Urgencias: error creando ingreso completo en la clínica', [
                'ref' => $refId,
                'documento' => $documento,
                'contrato_nit' => $contratoNit,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Busca la afiliación paciente+contrato en MAEPAC; si no existe esa combinación
     * exacta, la crea. Un mismo paciente puede tener varias filas (una por cada
     * contrato distinto que haya usado a lo largo del tiempo).
     * MTUCod/MTCodP usan un valor genérico confirmado como el más frecuente en la
     * práctica (Contributivo / Cotizante Nivel 1) — el personal de Admisiones lo
     * corrige después con el dato real del paciente. IPSCbr queda en NULL (su
     * valor no sigue un patrón claro y tiene peso legal en reportes RIPS).
     * No se revierte si algo falla después (mismo criterio que CAPBAS: es un
     * dato real y reutilizable).
     */
    private function buscarOCrearAfiliacionClinica(string $documento, string $tipoDocumento, string $contratoNit, string $refId): bool
    {
        $log = Log::channel('urgencias');
        $log->info('Urgencias: verificando afiliación en clínica (MAEPAC)', [
            'ref' => $refId,
            'documento' => $documento,
            'contrato_nit' => $contratoNit,
        ]);

        try {
            return DB::connection('sqlsrv')->transaction(function () use ($documento, $tipoDocumento, $contratoNit, $refId, $log) {
                $existe = DB::connection('sqlsrv')->table('MAEPAC')
                    ->where('MPCedu', $documento)
                    ->where('MPTDoc', $tipoDocumento)
                    ->where('MENNIT', $contratoNit)
                    ->exists();

                if ($existe) {
                    $log->info('Urgencias: afiliación ya existía en MAEPAC', [
                        'ref' => $refId,
                        'documento' => $documento,
                        'contrato_nit' => $contratoNit,
                    ]);
                    return false;
                }

                $maxOrd = DB::connection('sqlsrv')->table('MAEPAC')
                    ->where('MPCedu', $documento)
                    ->where('MPTDoc', $tipoDocumento)
                    ->lockForUpdate()
                    ->max('MPOrd');

                $siguienteOrd = ($maxOrd ?? 0) + 1;

                DB::connection('sqlsrv')->table('MAEPAC')->insert([
                    'MPCedu' => $documento,
                    'MPTDoc' => $tipoDocumento,
                    'MENNIT' => $contratoNit,
                    'MTUCod' => '1', // Contributivo (el régimen más común)
                    'MTCodP' => 'A', // Cotizante Nivel 1
                    'MPNoCa' => 0,
                    'MPstatus' => 'A',
                    'MPACMO' => 0.0000,
                    'MPOrd' => $siguienteOrd,
                    'UltCtvPrx' => 0,
                    'MPResExe' => null,
                    'MpPunSIS' => 0.0000,
                    'MpFicSIS' => 0,
                    'MPPoPla' => '',
                    'IPSCbr' => null, //SEDE IPS
                ]);

                $log->info('Urgencias: afiliación creada en MAEPAC (pendiente de verificar por Admisiones)', [
                    'ref' => $refId,
                    'documento' => $documento,
                    'contrato_nit' => $contratoNit,
                    'mp_ord' => $siguienteOrd,
                ]);
                return true;
            });
        } catch (\Throwable $e) {
            $log->error('Urgencias: error creando afiliación en MAEPAC', [
                'ref' => $refId,
                'documento' => $documento,
                'contrato_nit' => $contratoNit,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    /** Rollback: elimina el ingreso completo (LOGINGR, INGRESOMP, INGRESOS) en orden inverso */
    private function eliminarIngresoCompletoClinica(int $ingCsc, string $documento, string $tipoDocumento, string $refId): void
    {
        $log = Log::channel('urgencias');
        try {
            DB::connection('sqlsrv')->transaction(function () use ($ingCsc, $documento, $tipoDocumento) {
                DB::connection('sqlsrv')->table('TMPFAC')
                    ->where('TFCedu', $documento)->where('TFTDoc', $tipoDocumento)->where('TmCtvIng', $ingCsc)
                    ->delete();

                DB::connection('sqlsrv')->table('LOGINGR')
                    ->where('MPCedu', $documento)->where('MPTDoc', $tipoDocumento)->where('IngCsc', $ingCsc)
                    ->delete();

                DB::connection('sqlsrv')->table('INGRESOMP')
                    ->where('MPCedu', $documento)->where('MPTDoc', $tipoDocumento)->where('IngCsc', $ingCsc)
                    ->delete();

                DB::connection('sqlsrv')->table('INGRESOS')
                    ->where('MPCedu', $documento)->where('MPTDoc', $tipoDocumento)->where('IngCsc', $ingCsc)
                    ->delete();
            });
            $log->warning('Urgencias: ingreso completo revertido en la clínica', ['ref' => $refId, 'ing_csc' => $ingCsc]);
        } catch (\Throwable $e) {
            $log->critical('Urgencias: NO se pudo revertir el ingreso completo, requiere revisión manual', [
                'ref' => $refId,
                'ing_csc' => $ingCsc,
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

    /**
     * Revierte un turno completo (clínica + local) cuando falló la impresión del ticket.
     */
    public function revertirTurnoPorFalloImpresion(int $idTurno): void
    {
        $log = Log::channel('urgencias');
        $refId = (string) Str::uuid();

        $turno = Turno::with('paciente')->find($idTurno);
        if (!$turno) {
            $log->warning('Urgencias: intento de revertir turno inexistente', ['ref' => $refId, 'id_turno' => $idTurno]);
            return;
        }

        $log->warning('Urgencias: revirtiendo turno por fallo de impresión', [
            'ref' => $refId,
            'id_turno' => $idTurno,
            'numero_turno' => $turno->numero_turno,
        ]);

        $documento = $turno->paciente->numero_documento ?? null;
        $tipoDocumento = $turno->paciente->tipo_documento ?? null;
        $ingCsc = $turno->ingreso_consecutivo;

        if ($ingCsc !== null && $documento && $tipoDocumento) {
            $this->eliminarIngresoCompletoClinica((int) $ingCsc, $documento, $tipoDocumento, $refId);
        }

        $turno->delete();

        $log->info('Urgencias: turno revertido completamente por fallo de impresión', [
            'ref' => $refId,
            'id_turno' => $idTurno,
        ]);
    }
}
