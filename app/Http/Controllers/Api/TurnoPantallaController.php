<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Turno;
use Illuminate\Support\Facades\Log;


class TurnoPantallaController extends Controller
{
    public function ultimo()
    {
        $ultimoTurno = Turno::with(['paciente', 'modulo']) // Agregar relación con modulo
            ->whereIn('estado', ['llamado', 'llamado_facturar'])
            //->whereIn('motivo', ['Consulta Externa','Oncologia','Pedir Cita'])
            ->orderBy('updated_at', 'desc')
            ->first();

        return response()->json([
            'numero_turno' => $ultimoTurno?->numero_turno ?? null,
            'nombre' => $ultimoTurno?->paciente?->nombre ?? '',
            'apellido' => $ultimoTurno?->paciente?->apellido ?? '',
            'modulo' => $ultimoTurno?->modulo ?? null, // Agregar módulo completo
            'fk_modulo' => $ultimoTurno?->fk_modulo ?? null, // Agregar ID del módulo como respaldo
            'llamado_en'   => $ultimoTurno?->llamado_en,
        ]);
    }
    public function turnosLlamados()
    {
        return Turno::with(['paciente', 'modulo', 'consultorio'])   // ✅
            ->whereIn('estado', ['llamado', 'llamado_medico', 'llamado_facturar'])
            ->whereIn('motivo', ['Consulta Externa', 'Pedir Cita', 'Oncologia'])
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();
    }


    public function turnosMedico()
    {

        return Turno::with(['paciente', 'consultorio'])
            ->where('estado', 'llamado_medico')
            ->where('motivo', 'Consulta Externa')
            //->whereDate('updated_at', $hoy)
            ->orderBy('updated_at', 'desc')
            ->take(6)
            ->get();
    }


    public function turnoUltimoUrgencias()
    {
        $ultimoTurno = Turno::with('modulo')
            ->where('motivo', 'urgencias')
            ->where('estado_admisiones', 'llamado')
            ->orderBy('updated_at', 'desc')
            ->first();

        return response()->json([
            'id'           => $ultimoTurno?->id,
            'numero_turno' => $ultimoTurno?->numero_turno ?? null,
            'modulo'       => $ultimoTurno?->modulo ?? null,
            'fk_modulo'    => $ultimoTurno?->fk_modulo ?? null,
            'llamado_en'   => $ultimoTurno?->llamado_en,
            'paciente_urgencias' => $ultimoTurno?->paciente_urgencias,   // ⬅nueva linea para mostrar el nombre del paciente
        ]);
    }


    public function turnoMedicoUrgencias()
    {
        $turnoTriage = Turno::with('consultorio')
            ->where('motivo', 'urgencias')
            ->where('estado', 'llamado_medico')
            ->orderBy('updated_at', 'desc')
            ->first();

        $turnoConsulta = Turno::with('consultorioConsulta')
            ->where('motivo', 'urgencias')
            ->where('estado_consulta_medica', 'atendido')
            ->whereNotNull('fk_consultorio_consulta')
            ->orderBy('updated_at', 'desc')
            ->first();

        $turno = null;
        $tipo = null;
        $nombreConsultorio = null;

        if ($turnoTriage && $turnoConsulta) {
            if ($turnoTriage->updated_at->greaterThanOrEqualTo($turnoConsulta->updated_at)) {
                $turno = $turnoTriage;
                $tipo = 'triage';
                $nombreConsultorio = $turnoTriage->consultorio?->nombre;
            } else {
                $turno = $turnoConsulta;
                $tipo = 'consulta_medica';
                $nombreConsultorio = $turnoConsulta->consultorioConsulta?->nombre;
            }
        } elseif ($turnoTriage) {
            $turno = $turnoTriage;
            $tipo = 'triage';
            $nombreConsultorio = $turnoTriage->consultorio?->nombre;
        } elseif ($turnoConsulta) {
            $turno = $turnoConsulta;
            $tipo = 'consulta_medica';
            $nombreConsultorio = $turnoConsulta->consultorioConsulta?->nombre;
        }

        return response()->json([
            'id'           => $turno?->id,
            'numero_turno' => $turno?->numero_turno ?? null,
            'consultorio'  => $nombreConsultorio,
            'tipo'         => $tipo,
            'llamado_en'   => $turno?->llamado_en,
            'paciente_urgencias' => $turno?->paciente_urgencias
        ]);
    }


    public function turnosLlamadosUrgencias()
    {
        return Turno::with(['consultorio', 'modulo', 'consultorioConsulta'])
            ->whereDate('fecha', now()->toDateString())
            ->where('motivo', 'urgencias')
            ->where(function ($query) {
                $query->whereIn('estado', ['llamado', 'asignado', 'llamado_medico'])
                    ->orWhere('estado_admisiones', 'llamado')
                    ->orWhere('estado_consulta_medica', 'atendido');
            })
            ->orderBy('updated_at', 'desc')
            ->take(4)
            ->get();
    }

    public function turnoUltimoQuimioterapia()
    {
        $ultimoTurno = Turno::with(['paciente', 'modulo']) // Agregar relación con modulo
            ->where('estado', ['llamado'])
            ->where('motivo', 'Oncologia')
            ->orderBy('updated_at', 'desc')
            ->first();

        return response()->json([
            'numero_turno' => $ultimoTurno?->numero_turno ?? null,
            'nombre' => $ultimoTurno?->paciente?->nombre ?? '',
            'apellido' => $ultimoTurno?->paciente?->apellido ?? '',
            'modulo' => $ultimoTurno?->modulo ?? null, // Agregar módulo completo
            'fk_modulo' => $ultimoTurno?->fk_modulo ?? null, // Agregar ID del módulo como respaldo
            'llamado_en'   => $ultimoTurno?->llamado_en,
        ]);
    }

    public function turnosLlamadosQuimioterapia()
    {
        return Turno::with(['paciente', 'modulo'])   // ✅ se agrega 'modulo'
            ->where('estado', 'llamado')              // ⚠️ ver nota abajo
            ->where('motivo', 'Oncologia')
            ->orderBy('updated_at', 'desc')
            ->take(4)
            ->get();
    }
}
