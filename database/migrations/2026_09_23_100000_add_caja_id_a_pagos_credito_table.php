<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos_credito', function (Blueprint $table) {
            // Nullable a propósito: un admin puede registrar un pago sin tener
            // caja abierta (nunca abre caja), en cuyo caso el pago no queda
            // atado a ningún turno puntual — solo se completa cuando quien
            // registra el pago tiene una caja abierta en ese momento.
            $table->foreignId('caja_id')->nullable()->after('user_id')->constrained('cajas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pagos_credito', function (Blueprint $table) {
            $table->dropConstrainedForeignId('caja_id');
        });
    }
};
