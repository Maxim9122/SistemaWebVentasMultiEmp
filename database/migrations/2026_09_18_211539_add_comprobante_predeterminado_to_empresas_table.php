<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // 'remito' | 'A' | 'B' | 'C' — qué comprobante viene preseleccionado al
            // cobrar. Se valida contra la condición fiscal en tiempo real (ver
            // Empresa::comprobantePredeterminadoEfectivo()), así que un valor que
            // deja de ser válido (ej. cambio de condición fiscal) nunca rompe nada,
            // simplemente cae a 'remito'.
            // Sin `->after('factura_habilitada')` a propósito: esa columna recién
            // se crea en una migración posterior (2026_09_18_232635) — en un
            // `migrate` de una base nueva, de punta a punta, correr en orden por
            // fecha antes que esa otra tira "Column not found". El orden de
            // columnas en la tabla es puramente cosmético, no afecta nada.
            $table->string('comprobante_predeterminado')->default('remito');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('comprobante_predeterminado');
        });
    }
};
