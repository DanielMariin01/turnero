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
}