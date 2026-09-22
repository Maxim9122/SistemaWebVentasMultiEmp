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
        Schema::table('pedidos', function (Blueprint $table) {
            $table->foreignId('cobrado_por')->nullable()->after('notas')->constrained('users')->nullOnDelete();
            $table->string('forma_pago')->nullable()->after('cobrado_por');
            $table->string('tipo_comprobante')->nullable()->after('forma_pago');
            $table->timestamp('cobrado_at')->nullable()->after('tipo_comprobante');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cobrado_por');
            $table->dropColumn(['forma_pago', 'tipo_comprobante', 'cobrado_at']);
        });
    }
};
