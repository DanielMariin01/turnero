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
            ->where('motivo', 'Consulta Externa')
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

        return Turno::with('paciente')
            ->whereIn('estado', ['llamado', 'llamado_medico', 'llamado_facturar'])
            ->where('motivo', 'Consulta Externa')
            //->whereDate('updated_at', $hoy)
            ->orderBy('updated_at', 'desc')
            ->take(4)
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
            ->where('estado', 'llamado')
            ->orderBy('updated_at', 'desc')
            ->first();

        return response()->json([
            'id'           => $ultimoTurno?->id,
            'numero_turno' => $ultimoTurno?->numero_turno ?? null,
            'modulo'       => $ultimoTurno?->modulo ?? null,
            'fk_modulo'    => $ultimoTurno?->fk_modulo ?? null,
            'llamado_en'   => $ultimoTurno?->llamado_en,
        ]);
    }



    public function turnoMedicoUrgencias()
    {
        $turno = Turno::with('consultorio')
            ->where('motivo', 'urgencias')
            ->where('estado', 'llamado_medico')
            ->orderBy('updated_at', 'desc')
            ->first();

        return response()->json([
            'id'           => $turno?->id,
            'numero_turno' => $turno?->numero_turno ?? null,
            'consultorio'  => $turno?->consultorio?->nombre ?? null,
            'llamado_en'   => $turno?->llamado_en,
            'paciente_urgencias' => $turno?->paciente_urgencias
        ]);
    }


    public function turnosLlamadosUrgencias()
    {
        return Turno::with('consultorio')
            ->whereIn('estado', ['llamado', 'llamado_medico'])
            ->where('motivo', 'urgencias')
            //->whereDate('updated_at', $hoy)
            ->orderBy('updated_at', 'desc')
            ->take(4)
            ->get();
    }
}
