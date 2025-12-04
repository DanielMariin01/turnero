<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;

class SincronizarDatos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sincronizar-datos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
   public function handle()
{
    $this->info('Iniciando sincronización...');

    try {

        // Contar registros sin cargarlos en memoria
        $totalRegistros = DB::connection('sqlsrv')
            ->table('pacientes')
            ->count();

        $this->info("Se encontraron {$totalRegistros} registros");

        $procesados = 0;

        // Procesar en bloques de 1000
        DB::connection('sqlsrv')
            ->table('pacientes')
            ->orderBy('numero_documento') // importante para evitar saltos
            ->chunk(1000, function ($registros) use (&$procesados) {

                foreach ($registros as $registro) {
                    DB::connection('mysql')->table('paciente')->insert([
                        'nombre' => $registro->nombre,
                        'apellido' => $registro->apellido,
                        'Tipo_documento' => $registro->Tipo_documento,
                        'numero_documento' => $registro->numero_documento,
                        // agrega las demás columnas aquí
                    ]);

                    $procesados++;
                }

                // Mostrar avance
                echo "Procesados: {$procesados}\n";
            });

        $this->info("✓ Sincronización completada: {$procesados} registros");

    } catch (\Exception $e) {
        $this->error("Error: " . $e->getMessage());
        return 1;
    }

    return 0;
}

}
