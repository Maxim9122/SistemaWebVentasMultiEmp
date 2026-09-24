<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->decimal('monto_mercadopago', 12, 2)->nullable()->after('monto_fiado');
            $table->decimal('ajuste_mercadopago_porcentaje', 5, 2)->nullable()->after('ajuste_transferencia_porcentaje');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['monto_mercadopago', 'ajuste_mercadopago_porcentaje']);
        });
    }
};
