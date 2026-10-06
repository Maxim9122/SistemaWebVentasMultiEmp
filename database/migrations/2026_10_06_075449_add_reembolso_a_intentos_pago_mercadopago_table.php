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
        Schema::table('intentos_pago_mercadopago', function (Blueprint $table) {
            $table->timestamp('reembolsado_at')->nullable();
            $table->foreignId('reembolsado_por')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intentos_pago_mercadopago', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reembolsado_por');
            $table->dropColumn('reembolsado_at');
        });
    }
};
