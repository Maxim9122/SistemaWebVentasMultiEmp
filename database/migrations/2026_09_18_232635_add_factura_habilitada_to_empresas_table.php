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
            // Interruptor operativo, independiente de la condición fiscal: permite
            // al admin pausar temporalmente la facturación (por ejemplo, un problema
            // puntual con AFIP) sin desarmar la condición fiscal ni el certificado
            // ya configurado. Activo por defecto.
            $table->boolean('factura_habilitada')->default(true)->after('condicion_fiscal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('factura_habilitada');
        });
    }
};
