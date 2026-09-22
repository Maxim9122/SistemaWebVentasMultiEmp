<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Se usa SQL crudo (MODIFY COLUMN) en vez de Blueprint::change() para no
 * sumar doctrine/dbal como dependencia nueva solo por este cambio puntual —
 * el índice único (empresa_id, cuit) no necesita tocarse: MySQL ya trata
 * cada NULL como distinto entre sí, así que varios clientes sin CUIT nunca
 * chocan entre ellos.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE clientes MODIFY cuit VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE clientes SET cuit = '' WHERE cuit IS NULL");
        DB::statement('ALTER TABLE clientes MODIFY cuit VARCHAR(255) NOT NULL');
    }
};
