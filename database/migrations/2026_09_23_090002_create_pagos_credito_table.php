<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_credito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            // El cliente paga contra su saldo general, no contra una venta
            // puntual — un pago parcial puede cubrir varias ventas fiadas a la
            // vez, así que no se ata a un `pedido_id`.
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            // Quién lo cargó (cajero, cajero_vendedor o admin).
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('monto_efectivo', 12, 2)->default(0);
            $table->decimal('monto_tarjeta', 12, 2)->default(0);
            $table->decimal('monto_transferencia', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['empresa_id', 'cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_credito');
    }
};
