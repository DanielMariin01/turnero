<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Turno;
use Carbon\Carbon;

class AlertaMedicaController extends Controller
{
    // Misma lista que ya tienes en ConsultaMedicaPrepagadaResource.php
    private const CONTRATOS_PREPAGADA = [
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

    public function estadoEspera()
    {
        $fechaLimite = now()->subDay()->toDateString();
        $ahora = now();

        $triage = Turno::whereDate('fecha', '>=', $fechaLimite)
            ->where('estado', 'asignado')
            ->where('motivo', 'urgencias')
            ->get(['id_turno', 'numero_turno', 'paciente_urgencias', 'fecha', 'hora_atendido']);

        $consulta = Turno::whereDate('fecha', '>=', $fechaLimite)
            ->where('estado_consulta_medica', 'pendiente')
            ->where('motivo', 'urgencias')
            ->whereNotIn('contrato_nit', self::CONTRATOS_PREPAGADA)
            ->get(['id_turno', 'numero_turno', 'paciente_urgencias', 'fecha', 'hora_ingreso_consulta_medica']);

        $prepagada = Turno::whereDate('fecha', '>=', $fechaLimite)
            ->where('estado_consulta_medica', 'pendiente')
            ->where('motivo', 'urgencias')
            ->whereIn('contrato_nit', self::CONTRATOS_PREPAGADA)
            ->get(['id_turno', 'numero_turno', 'paciente_urgencias', 'fecha', 'hora_ingreso_consulta_medica']);

        $mapear = function ($turnos, string $categoria, string $campoHora) use ($ahora) {
            return $turnos->map(function ($turno) use ($categoria, $campoHora, $ahora) {
                $horaTexto = $turno->{$campoHora};
                $minutos = 0;

                if ($horaTexto) {
                    $momento = Carbon::parse($turno->fecha . ' ' . $horaTexto);
                    $minutos = max(0, (int) $momento->diffInMinutes($ahora));
                }

                return [
                    'id_turno' => $turno->id_turno,
                    'numero_turno' => $turno->numero_turno,
                    'nombre' => $turno->paciente_urgencias,
                    'categoria' => $categoria,
                    'hora' => $horaTexto,
                    'minutos_espera' => $minutos,
                    'tiempo_espera' => $this->formatearTiempo($minutos),
                ];
            })->values();
        };

        $pacientes = collect()
            ->merge($mapear($triage, 'triage', 'hora_atendido'))
            ->merge($mapear($consulta, 'consulta_medica', 'hora_ingreso_consulta_medica'))
            ->merge($mapear($prepagada, 'consulta_medica_prepagada', 'hora_ingreso_consulta_medica'));

        return response()->json([
            'en_espera' => $pacientes->count(),
            'detalle' => [
                'triage' => $triage->count(),
                'consulta_medica' => $consulta->count(),
                'consulta_medica_prepagada' => $prepagada->count(),
            ],
            'pacientes' => $pacientes,
        ]);
    }

    /**
     * Convierte minutos a texto legible: "1 hora 26 minutos", "45 minutos", "2 horas".
     */
    private function formatearTiempo(int $minutos): string
    {
        if ($minutos < 1) {
            return 'menos de 1 minuto';
        }

        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;

        $partes = [];
        if ($horas > 0) {
            $partes[] = $horas . ' ' . ($horas === 1 ? 'hora' : 'horas');
        }
        if ($resto > 0 || $horas === 0) {
            $partes[] = $resto . ' ' . ($resto === 1 ? 'minuto' : 'minutos');
        }

        return implode(' ', $partes);
    }
}
