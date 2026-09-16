<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ClinicaController extends Controller
{
    public function contratos()
    {
        $contratos = Cache::remember('clinica_contratos', now()->addHours(6), function () {
            return DB::connection('sqlsrv')
                ->table('MAEEMP')
                ->select('MENNIT as nit', DB::raw('RTRIM(MENOMB) as nombre_original'))
                ->orderBy('MENOMB')
                ->get()
                ->map(function ($contrato) {
                    $nombreLimpio = preg_replace('/^\(ARL\)\s*/i', '', $contrato->nombre_original);
                    $nombreLimpio = preg_replace('/^ADSCRITO\s*-\s*/i', '', $nombreLimpio);
                    $nombreLimpio = preg_replace('/\s*\*U\*\s*$/i', '', $nombreLimpio);
                    return [
                        'nit' => $contrato->nit,
                        'nombre' => trim($nombreLimpio),
                    ];
                });
        });

        return response()->json($contratos);
    }

    public function paciente($documento)
    {
        $paciente = DB::connection('sqlsrv')->table('CAPBAS')
            ->where('MPCedu', $documento)
            ->first();

        if (!$paciente) {
            return response()->json(['message' => 'Paciente no encontrado en la clínica'], 404);
        }

        // Buscar el contrato de la visita más reciente (si existe alguna)
        $ultimoIngreso = DB::connection('sqlsrv')->table('INGRESOS')
            ->where('MPCedu', $documento)
            ->whereNotNull('IngNit')
            ->orderBy('IngFecAdm', 'desc')
            ->first();

        $contrato = null;
        if ($ultimoIngreso && trim($ultimoIngreso->IngNit ?? '') !== '') {
            $contratoRow = DB::connection('sqlsrv')->table('MAEEMP as M')
                ->join('EMPRESS as E', 'M.MEcntr', '=', 'E.MEcntr')
                ->where('M.MENNIT', trim($ultimoIngreso->IngNit))
                ->select('M.MENNIT as nit', DB::raw('RTRIM(E.EmpDsc) as nombre'))
                ->first();

            if ($contratoRow) {
                $contrato = [
                    'nit' => trim($contratoRow->nit),
                    'nombre' => $contratoRow->nombre,
                ];
            }
        }

        $partesNombre = array_filter([trim($paciente->MPNom1 ?? ''), trim($paciente->MPNom2 ?? '')]);
        $partesApellido = array_filter([trim($paciente->MPApe1 ?? ''), trim($paciente->MPApe2 ?? '')]);

        // Formatear fecha de nacimiento a YYYY-MM-DD para el <input type="date">
        $fechaNacimiento = null;
        if (!empty($paciente->MPFchN)) {
            $fechaNacimiento = \Carbon\Carbon::parse($paciente->MPFchN)->format('Y-m-d');
        }

        return response()->json([
            'nombre' => implode(' ', $partesNombre),
            'apellido' => implode(' ', $partesApellido),
            'tipo_documento' => trim($paciente->MPTDoc),
            'numero_documento' => trim($paciente->MPCedu),
            'fecha_nacimiento' => $fechaNacimiento,
            'sexo' => trim($paciente->MPSexo ?? ''),
            'contrato' => $contrato,
        ]);
    }
}
