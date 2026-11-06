<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda qué usuario llamó cada turno. Es NULL (los turnos existentes y el código actual no la usan,
 * así que nada se rompe). Va al final de la tabla y sin llave foránea, para no bloquear la tabla en producción.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turno', function (Blueprint $table) {
            $table->unsignedBigInteger('fk_users_llamado')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('turno', function (Blueprint $table) {
            $table->dropColumn('fk_users_llamado');
        });
    }
};
