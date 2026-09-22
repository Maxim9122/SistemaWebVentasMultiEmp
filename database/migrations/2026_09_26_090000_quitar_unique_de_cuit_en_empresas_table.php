<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un CUIT repetido en /registro-empresa ya no se bloquea en seco (queda
 * pendiente y se le avisa al superadmin para que decida) — la restricción
 * única a nivel de base de datos (agregada en
 * 2026_09_18_134958_add_unique_a_cuit_en_empresas_table) lo impedía incluso
 * después de sacar la regla `unique` de la validación, así que hay que
 * sacarla también acá.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropUnique(['cuit']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->unique('cuit');
        });
    }
};
