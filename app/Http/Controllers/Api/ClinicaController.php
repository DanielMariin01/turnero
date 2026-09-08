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
                ->table('MAEEMP as M')
                ->join('EMPRESS as E', 'M.MEcntr', '=', 'E.MEcntr')
                ->select('M.MENNIT as nit', DB::raw('RTRIM(E.EmpDsc) as nombre'))
                ->orderBy('E.EmpDsc')
                ->get();
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

        return response()->json([
            'nombre' => trim($paciente->MPNom1 . ' ' . $paciente->MPNom2),
            'apellido' => trim($paciente->MPApe1 . ' ' . $paciente->MPApe2),
            'tipo_documento' => trim($paciente->MPTDoc),
            'numero_documento' => trim($paciente->MPCedu),
        ]);
    }
}
