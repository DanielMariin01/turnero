<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;

class SincronizarDatos extends Command
{
    protected $signature = 'app:sincronizar-datos';
    protected $description = 'Sincroniza datos de pacientes desde SQL Server a MySQL (solo agrega nuevos registros)';

    public function handle()
    {
        $this->info('Iniciando sincronización...');

        try {
            // Contar registros
            $totalRegistros = DB::connection('sqlsrv')
                ->table('pacientes')
                ->count();

            $this->info("Total de registros en origen: {$totalRegistros}");

            if ($totalRegistros === 0) {
                $this->warn('No hay registros para sincronizar');
                return 0;
            }

            $procesados = 0;
            $omitidos = 0;
            $errores = 0;
            $chunkSize = 1000;

            // Barra de progreso
            $bar = $this->output->createProgressBar($totalRegistros);
            $bar->start();

            // Procesar en bloques
            DB::connection('sqlsrv')
                ->table('pacientes')
                ->orderBy('numero_documento')
                ->chunk($chunkSize, function ($registros) use (&$procesados, &$omitidos, &$errores, $bar) {
                    
                    // Extraer números de documento del chunk actual
                    $documentosChunk = $registros->pluck('numero_documento')->toArray();
                    
                    // Verificar cuáles ya existen en MySQL (consulta por lote)
                    $documentosExistentesChunk = DB::connection('mysql')
                        ->table('paciente')
                        ->whereIn('numero_documento', $documentosChunk)
                        ->pluck('numero_documento')
                        ->toArray();

                    // Preparar datos para inserción masiva
                    $datosParaInsertar = [];

                    foreach ($registros as $registro) {
                        try {
                            // Verificar si el documento ya existe
                            if (in_array($registro->numero_documento, $documentosExistentesChunk)) {
                                $omitidos++;
                                $bar->advance();
                                continue;
                            }

                            $datosParaInsertar[] = [
                                'nombre' => $registro->nombre,
                                'apellido' => $registro->apellido,
                                'Tipo_documento' => $registro->Tipo_documento,
                                'numero_documento' => $registro->numero_documento,
                                'created_at' => now(),
                                'updated_at' => now(),
                                // Agrega las demás columnas aquí
                            ];
                        } catch (Exception $e) {
                            $errores++;
                            $bar->advance();
                        }
                    }

                    // Inserción masiva
                    if (!empty($datosParaInsertar)) {
                        DB::connection('mysql')->table('paciente')->insert($datosParaInsertar);
                        $procesados += count($datosParaInsertar);
                        $bar->advance(count($datosParaInsertar));
                    }
                });

            $bar->finish();
            $this->newLine(2);
            
            $this->info("✓ Sincronización completada");
            $this->info("  - Registros nuevos insertados: {$procesados}");
            $this->info("  - Registros omitidos (ya existían): {$omitidos}");
            if ($errores > 0) {
                $this->warn("  - Registros con error: {$errores}");
            }

            return 0;

        } catch (Exception $e) {
            $this->newLine();
            $this->error("Error fatal: " . $e->getMessage());
            $this->error("Línea: " . $e->getLine());
            $this->error("Archivo: " . $e->getFile());
            return 1;
        }
    }
}