<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            // El cajero/cajero_vendedor dueño de esta apertura — una caja es
            // siempre de una sola persona, no se comparte entre turnos.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('abierta_at');
            // Fondo inicial en efectivo (para dar vuelto), obligatorio al abrir.
            $table->decimal('monto_apertura', 12, 2);
            $table->text('nota_apertura')->nullable();
            // Null = todavía abierta. Es la forma de saber el estado, sin una
            // columna "estado" aparte.
            $table->timestamp('cerrada_at')->nullable();
            // Lo que el cajero contó físicamente en efectivo al cerrar — se
            // compara contra el esperado (fondo + ventas efectivo - egresos
            // efectivo) calculado al vuelo, nunca se guarda el esperado.
            $table->decimal('monto_cierre_declarado', 12, 2)->nullable();
            $table->text('nota_cierre')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'abierta_at']);
            // Búsqueda frecuente: "¿este usuario tiene una caja abierta ahora?"
            $table->index(['user_id', 'cerrada_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};
