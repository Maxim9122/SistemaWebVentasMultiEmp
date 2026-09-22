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
            $table->foreignId('cliente_id')->nullable()->after('cobrado_at')->constrained('clientes')->nullOnDelete();
            $table->string('tipo_factura')->nullable()->after('cliente_id');
            $table->decimal('monto_efectivo', 12, 2)->nullable()->after('tipo_factura');
            $table->decimal('monto_tarjeta', 12, 2)->nullable()->after('monto_efectivo');
            $table->decimal('monto_transferencia', 12, 2)->nullable()->after('monto_tarjeta');
            $table->decimal('ajuste_efectivo_porcentaje', 5, 2)->nullable()->after('monto_transferencia');
            $table->decimal('ajuste_tarjeta_porcentaje', 5, 2)->nullable()->after('ajuste_efectivo_porcentaje');
            $table->decimal('ajuste_transferencia_porcentaje', 5, 2)->nullable()->after('ajuste_tarjeta_porcentaje');
            $table->decimal('total_cobrado', 12, 2)->nullable()->after('ajuste_transferencia_porcentaje');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cliente_id');
            $table->dropColumn([
                'tipo_factura',
                'monto_efectivo',
                'monto_tarjeta',
                'monto_transferencia',
                'ajuste_efectivo_porcentaje',
                'ajuste_tarjeta_porcentaje',
                'ajuste_transferencia_porcentaje',
                'total_cobrado',
            ]);
        });
    }
};
