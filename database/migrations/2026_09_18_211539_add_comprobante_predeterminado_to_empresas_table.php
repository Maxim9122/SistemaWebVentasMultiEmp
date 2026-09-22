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
            $table->string('comprobante_predeterminado')->default('remito')->after('factura_habilitada');
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
