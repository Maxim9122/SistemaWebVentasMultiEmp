<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // Antes el cliente solo quedaba registrado en `facturas.cliente_id`
            // (cuando la venta era con Factura) — para fiar hace falta saber
            // quién debe la plata aunque la venta sea con remito, así que el
            // cliente pasa a vivir también acá, siempre que se haya elegido uno.
            $table->foreignId('cliente_id')->nullable()->after('cobrado_por')->constrained('clientes')->nullOnDelete();
            // Cuánto de esta venta quedó fiado (no cobrado en el momento). Null/0
            // en la inmensa mayoría de las ventas — solo se completa cuando la
            // empresa tiene permite_fiado activo y se usó ese medio.
            $table->decimal('monto_fiado', 12, 2)->nullable()->after('monto_transferencia');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cliente_id');
            $table->dropColumn('monto_fiado');
        });
    }
};
