<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // Nullable a propósito: ventas de antes de esta función, o
            // cobradas por un rol al que no se le exige caja abierta, quedan
            // sin caja asociada. Es solo una referencia — la venta sigue
            // viviendo una única vez en `pedidos`, esto no duplica nada.
            $table->foreignId('caja_id')->nullable()->after('cobrado_por')->constrained('cajas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('caja_id');
        });
    }
};
