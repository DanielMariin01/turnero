<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActualizarTurnosCommand extends Command
{
    protected $signature = 'turnos:actualizar';
    protected $description = 'Actualiza estados de turnos automáticamente';

    public function handle()
    {
        try {
            // Tarea 1: Marcar turnos antiguos como no_atendido (solo a medianoche)
            $this->actualizarTurnosAntiguos();
            
            // Tarea 2: Actualizar llamado_medico a facturar (siempre)
            $this->actualizarTurnosFacturar();

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            Log::error('Error en comando turnos:actualizar', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return self::FAILURE;
        }
    }

    /**
     * Marca turnos de días anteriores como no_atendido
     * Solo se ejecuta entre las 00:00 y 00:05
     */
    private function actualizarTurnosAntiguos()
    {
        $horaActual = Carbon::now();
        
        // Solo ejecutar a medianoche
        if ($horaActual->hour === 0 && $horaActual->minute < 5) {
            $hoy = Carbon::today();
            $noAtendidos = DB::table('turno')
                ->whereDate('fecha', '<', $hoy)
                ->where('estado', 'en_espera')
                ->update([
                    'estado' => 'no_atendido',
                    'updated_at' => now(),
                ]);

            if ($noAtendidos > 0) {
                $this->info("✅ Turnos marcados como no atendidos: {$noAtendidos}");
                Log::info("Turnos no atendidos actualizados: {$noAtendidos}");
            }
        }
    }

    /**
     * Actualiza turnos de llamado_medico a facturar después de 15 minutos
     * Se ejecuta siempre (cada 5 minutos)
     */
    private function actualizarTurnosFacturar()
    {
        $limite = Carbon::now()->subMinutes(10);
        $facturar = DB::table('turno')
            ->where('estado', 'llamado_medico')
            ->where('updated_at', '<=', $limite)
            ->update([
                'estado' => 'facturar',
                'updated_at' => now(),
            ]);

        if ($facturar > 0) {
            $this->info("✅ Turnos actualizados a facturar: {$facturar}");
            Log::info("Turnos actualizados a facturar: {$facturar}");
        }
    }
}