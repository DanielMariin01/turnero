<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solo afecta la tabla local `turno` (MySQL). No toca la base de la clínica (sqlsrv).
     * prioridad_especial: 1 = SÍ (menor de 10 años, 60+ años, embarazo o discapacidad), 0 = NO, NULL = turnos anteriores.
     */
    public function up(): void
    {
        Schema::connection('mysql')->table('turno', function (Blueprint $table) {
            $table->boolean('prioridad_especial')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('turno', function (Blueprint $table) {
            $table->dropColumn('prioridad_especial');
        });
    }
};
