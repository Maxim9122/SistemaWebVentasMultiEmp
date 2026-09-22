<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            // Hasta ahora la relación pedido→factura era de un solo sentido
            // (pedidos.factura_id). Al editar una venta ya facturada, el pedido
            // pasa a apuntar a una factura NUEVA y la vieja (con su nota de
            // crédito) queda histórica — sin esta columna, no habría forma de
            // saber de qué pedido salió esa factura vieja.
            $table->foreignId('pedido_id')->nullable()->after('empresa_id')->constrained('pedidos')->nullOnDelete();
        });

        // Backfill: para las facturas que ya existen, el pedido que las originó
        // es el que hoy las tiene como vigente (pedidos.factura_id).
        DB::table('pedidos')
            ->whereNotNull('factura_id')
            ->select('id', 'factura_id')
            ->get()
            ->each(function ($pedido) {
                DB::table('facturas')->where('id', $pedido->factura_id)->update(['pedido_id' => $pedido->id]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pedido_id');
        });
    }
};
