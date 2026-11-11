<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La cédula identifica a cada persona en el ingreso único desde el portal, así que no puede repetirse.
 * Solo agrega un índice: no cambia columnas ni datos. Si ya hubiera cédulas repetidas se detiene
 * antes de tocar la tabla y dice cuáles son.
 */
return new class extends Migration
{
    public function up(): void
    {
        $repetidas = DB::table('users')
            ->select('cedula')
            ->groupBy('cedula')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('cedula');

        if ($repetidas->isNotEmpty()) {
            throw new RuntimeException('Hay cédulas repetidas en users: ' . $repetidas->implode(', ') . '. Corrígelas y vuelve a ejecutar.');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('cedula');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['cedula']);
        });
    }
};
