<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;




class ActualizarTurnosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'turnos:actualizar';

    /**
     * The console command description.
     *
     * @var string
     */
   protected $description = 'Marca como no atendidos los turnos de días anteriores si no fueron atendidos.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hoy = Carbon::today();

        $affected = DB::table('turno')
            ->whereDate('fecha', '<', $hoy)
            ->where('estado', '!=', 'en espera')
            ->update([
                'estado' => 'no atendido',
                'updated_at' => now(),
            ]);

        $this->info("✅ Turnos actualizados: {$affected}");
    }
}
