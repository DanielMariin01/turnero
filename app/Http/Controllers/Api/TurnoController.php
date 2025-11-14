<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Turno;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TurnoController extends Controller
{
    /**
     * Genera un código único de turno basado en el motivo.
     */
   //// private function generarCodigo(string $motivo, int $intentosMax = 6)
    ////{
        // Toma las dos primeras letras del motivo (solo letras)
       //// $letras = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $motivo), 0, 2) ?: 'TU');

        // Intenta generar un código único
        ////for ($i = 0; $i < $intentosMax; $i++) {
           //// $numero = mt_rand(0, 1000); // número entre 0 y 99
            ////$codigo = $letras . str_pad($numero, 2, '0', STR_PAD_LEFT);
////
            ////if (!Turno::where('numero_turno', $codigo)->exists()) {
              ////  return $codigo;
           //// }
       //// }

        // Si hay colisiones, genera un fallback con caracteres aleatorios
       // return $letras . strtoupper(substr(Str::random(4), 0, 4));
   // }

    /**
     * Guarda un nuevo turno en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fk_paciente' => 'nullable|integer',
            'motivo' => 'required|string|max:250',
            'condicion' => 'nullable|string|max:250',
        ]);

        // Generar número de turno único
       $fecha = Carbon::today()->toDateString();
        $hora = Carbon::now()->toTimeString();

        // Buscar el último turno de hoy
        $ultimoTurno = Turno::whereDate('fecha', $fecha)
            ->orderBy('id_turno', 'desc')
            ->first();

        // Si hay uno, sumamos +1, si no, empezamos en 1
         if ($ultimoTurno) {
            preg_match('/\d+$/', $ultimoTurno->numero_turno, $matches);
            $ultimoNumero = isset($matches[0]) ? intval($matches[0]) : 0;
            $numero = $ultimoNumero + 1;
        } else {
            $numero = 1;
        }

      
        $letras = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $validated['motivo']), 0, 2)) ?: 'TU';
        $codigoTurno = $letras . $numero;

        // Crear el turno
        $turno = Turno::create([
            'fk_paciente' => $validated['fk_paciente'] ?? null,
            'numero_turno' => $codigoTurno,
            'motivo' => $validated['motivo'],
            'condicion' => $validated['condicion'] ?? null,
            'fecha' => $fecha,
            'hora' => $hora,
            'estado' => 'en_espera',
        ]);


        $turno->load('paciente');

        // Respuesta JSON
        return response()->json([
            'message' => 'Su turno se ha generado correctamente.',
            'turno' => $turno,
        ], 201);
    }
}
