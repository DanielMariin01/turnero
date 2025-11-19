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
        $ultimoTurno = Turno::where('estado', 'llamado')
            ->orderBy('updated_at', 'desc')
            ->first();


        return response()->json([
            'numero_turno' => $ultimoTurno?->numero_turno ?? null,
            'nombre' => $ultimoTurno?->paciente?->nombre ?? '',
            'apellido' => $ultimoTurno?->paciente?->apellido ?? '',
        ]);
    }

   public function turnosLlamados(){


  

    return Turno::with('paciente')
    ->where('estado','llamado')
   //->whereDate('updated_at', $hoy)
   ->orderBy('updated_at', 'desc')
   ->take(5) 
    ->get();

   }


}
