<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->boolean('permite_pago_qr')->default(false)->after('permite_fiado');
            $table->decimal('ajuste_mercadopago_porcentaje', 5, 2)->default(0)->after('ajuste_transferencia_porcentaje');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['permite_pago_qr', 'ajuste_mercadopago_porcentaje']);
        });
    }
};
