<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Turno;

class AlertaMedicaController extends Controller
{
    public function estadoEspera()
    {
        $fechaLimite = now()->subDay()->toDateString();

        // Misma lista que ya tienes en ConsultaMedicaPrepagadaResource.php
        $contratosPrepagada = [
            '37209',
            'CPJ-01',
            'IPS-006',
            'IPS-007',
            'IPS-008',
            '46217',
            '37086',
            'AXA002',
            'AXA003',
            'P13001',
            'SOAT002',
            'IPS-BMI',
            'IPS-MBI-2',
            'IPS-MBI-3',
            'EMP028',
            'MEDSOAT',
            'COLM-01',
            'ECO001',
            'PARTICULARES',
        ];

        $enTriage = Turno::whereDate('fecha', '>=', $fechaLimite)
            ->where('estado', 'asignado')
            ->where('motivo', 'urgencias')
            ->count();

        $enConsultaMedica = Turno::whereDate('fecha', '>=', $fechaLimite)
            ->where('estado_consulta_medica', 'pendiente')
            ->where('motivo', 'urgencias')
            ->whereNotIn('contrato_nit', $contratosPrepagada)
            ->count();

        $enConsultaMedicaPrepagada = Turno::whereDate('fecha', '>=', $fechaLimite)
            ->where('estado_consulta_medica', 'pendiente')
            ->where('motivo', 'urgencias')
            ->whereIn('contrato_nit', $contratosPrepagada)
            ->count();

        $total = $enTriage + $enConsultaMedica + $enConsultaMedicaPrepagada;

        return response()->json([
            'en_espera' => $total,
            'detalle' => [
                'triage' => $enTriage,
                'consulta_medica' => $enConsultaMedica,
                'consulta_medica_prepagada' => $enConsultaMedicaPrepagada,
            ],
        ]);
    }
}
