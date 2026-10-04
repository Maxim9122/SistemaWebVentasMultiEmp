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
        Schema::create('intentos_pago_mercadopago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pedido_id')->constrained()->cascadeOnDelete();
            // Cajero vigente al momento del intento — ProcesadorDeCobro::cobrar()
            // pide un User $cajero, y el webhook no tiene sesión para resolverlo.
            $table->foreignId('user_id')->constrained();
            $table->decimal('monto', 12, 2);
            $table->string('external_reference')->unique();
            $table->string('mp_merchant_order_id')->nullable();
            $table->string('estado')->default('pendiente'); // pendiente | aprobado | expirado | cancelado
            $table->timestamp('expira_at')->nullable();
            // Array $montos completo validado al momento del cobro (por si es un
            // pago dividido, ej. efectivo+mercadopago) — el webhook necesita
            // reproducir el cobrar() original sin tener la request HTTP disponible.
            $table->json('montos_json')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intentos_pago_mercadopago');
    }
};
