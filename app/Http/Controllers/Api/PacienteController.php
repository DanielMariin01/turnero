<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Paciente;


class PacienteController extends Controller
{
    
   public function show($numero_documento)
    {
        $paciente = Paciente::where('numero_documento', $numero_documento)->first();

        if (!$paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }

        return response()->json($paciente);
    }

    
}
