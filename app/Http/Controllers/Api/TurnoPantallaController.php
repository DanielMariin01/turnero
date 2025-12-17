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

   public function turnosLlamados(){


  

    return Turno::with('paciente')
     ->whereIn('estado', ['llamado', 'llamado_medico','llamado_facturar'])
   //->whereDate('updated_at', $hoy)
   ->orderBy('updated_at', 'desc')
   ->take(6) 
    ->get();

   }

   
  public function turnosMedico(){

    return Turno::with(['paciente', 'consultorio'])
        ->where('estado','llamado_medico')
        //->whereDate('updated_at', $hoy)
        ->orderBy('updated_at', 'desc')
        ->take(6) 
        ->get();
}

}
