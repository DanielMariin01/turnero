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

    public function crear_paciente(Request $request)
    {


  $validated = $request->validate([
        'nombre' => 'required|string|max:250',
        'apellido' => 'required|string|max:250',
        'tipo_documento' => 'required|string|max:10',
        'numero_documento' => 'required|string|max:150|unique:paciente,numero_documento',

    ]);

    $paciente = Paciente::create($validated);

    return response()->json($paciente, 201);


    }

    
}
