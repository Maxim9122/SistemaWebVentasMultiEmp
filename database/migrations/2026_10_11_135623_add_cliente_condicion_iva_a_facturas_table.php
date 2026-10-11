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
        Schema::table('facturas', function (Blueprint $table) {
            // Snapshot al momento de facturar, mismo criterio que
            // cliente_nombre/cliente_cuit — si el cliente cambia su
            // condición después, esta factura vieja no se altera sola.
            $table->string('cliente_condicion_iva')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn('cliente_condicion_iva');
        });
    }
};
