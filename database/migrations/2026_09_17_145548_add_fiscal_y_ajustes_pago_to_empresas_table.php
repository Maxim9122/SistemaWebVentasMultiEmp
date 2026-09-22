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
            $table->string('condicion_fiscal')->nullable()->after('permite_cambiar_precio_venta');
            $table->decimal('ajuste_efectivo_porcentaje', 5, 2)->default(0)->after('condicion_fiscal');
            $table->decimal('ajuste_tarjeta_porcentaje', 5, 2)->default(0)->after('ajuste_efectivo_porcentaje');
            $table->decimal('ajuste_transferencia_porcentaje', 5, 2)->default(0)->after('ajuste_tarjeta_porcentaje');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'condicion_fiscal',
                'ajuste_efectivo_porcentaje',
                'ajuste_tarjeta_porcentaje',
                'ajuste_transferencia_porcentaje',
            ]);
        });
    }
};
