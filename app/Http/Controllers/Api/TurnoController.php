<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Turno;
use Carbon\Carbon;
use App\Models\Paciente;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;



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


    public function storeUrgencias(Request $request, \App\Services\ClinicaIntegrationService $clinica)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:250',
            'apellido' => 'required|string|max:250',
            'tipo_documento' => 'required|string|max:10',
            'numero_documento' => 'required|string|max:150',
            'fecha_nacimiento' => 'required|date',
            'sexo' => 'required|string|max:1|in:M,F',
            'contrato_nit' => 'required|string|max:20',
            'contrato_nombre' => 'required|string|max:250',
        ]);

        $tiposConEdadExacta = ['CN', 'RC', 'TI', 'CC'];
        $tiposConEdadGenerica = ['AS', 'MS'];

        if (in_array($validated['tipo_documento'], $tiposConEdadExacta)) {
            $edad = \Carbon\Carbon::parse($validated['fecha_nacimiento'])->age;

            $tiposPermitidos = match (true) {
                $edad < 1 => ['CN', 'RC'],   // ← ahora permite CN y RC
                $edad < 7 => ['RC'],
                $edad < 18 => ['TI'],
                default => ['CC'],
            };

            if (!in_array($validated['tipo_documento'], $tiposPermitidos)) {
                $esperados = implode(' o ', $tiposPermitidos);

                return response()->json([
                    'message' => "Según la fecha de nacimiento, el paciente tiene {$edad} años y debería tener tipo de documento {$esperados}, no {$validated['tipo_documento']}. Por favor corrija el tipo de documento.",
                ], 422);
            }
        } elseif (in_array($validated['tipo_documento'], $tiposConEdadGenerica)) {
            $edad = \Carbon\Carbon::parse($validated['fecha_nacimiento'])->age;
            $esIncompatible = ($validated['tipo_documento'] === 'AS' && $edad < 18)
                || ($validated['tipo_documento'] === 'MS' && $edad >= 18);

            if ($esIncompatible) {
                return response()->json([
                    'message' => "Según la fecha de nacimiento, el paciente tiene {$edad} años, lo cual no es compatible con el tipo de documento seleccionado.",
                ], 422);
            }
        }

        try {
            $turno = $clinica->crearTurnoUrgencias($validated);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Su turno se ha generado correctamente.',
            'turno' => $turno,
        ], 201);
    }

    public function imprimir($id_turno)
    {
        try {
            $turno = Turno::findOrFail($id_turno);

            // Comandos ESC/POS (los que ya funcionaban)
            $esc  = "\x1B\x40";            // Reset
            $esc .= "\x1B\x61\x01";        // Centrar
            $esc .= "\x1B\x21\x30";        // Texto grande
            $esc .= "URGENCIAS\n\n";
            $esc .= "\x1B\x21\x20";        // Normal
            $esc .= "Turno\n\n";
            $esc .= "\x1B\x21\x38";        // MUY grande
            $esc .= $turno->numero_turno . "\n\n";
            $esc .= "\x1B\x21\x00";        // Normal
            $esc .= Carbon::parse($turno->fecha . ' ' . $turno->hora)->format('d/m/Y H:i:s') . "\n\n";
            $esc .= "Espere su llamado\n\n\n";
            $esc .= "\x1D\x56\x00";        // Corte completo

            // Convertir a base64 para enviar por JSON
            return response()->json([
                'ok' => true,
                'comandos' => base64_encode($esc)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Turno no encontrado'], 404);
        }
    }

    public function revertirPorImpresion($id_turno, \App\Services\ClinicaIntegrationService $clinica)
    {
        $clinica->revertirTurnoPorFalloImpresion((int) $id_turno);
        return response()->json(['message' => 'Turno revertido correctamente.']);
    }
}
