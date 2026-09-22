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
            // Primero se agregan los índices nuevos y recién después se borra el viejo:
            // MySQL exige que las FK de empresa_id/vendedor_id sigan cubiertas por algún
            // índice en todo momento, y el compuesto de abajo es el que las cubre hoy.
            $table->index(['vendedor_id', 'estado']);
            $table->index(['empresa_id', 'estado']);
            $table->dropIndex(['empresa_id', 'vendedor_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->index(['empresa_id', 'vendedor_id', 'estado']);
            $table->dropIndex(['vendedor_id', 'estado']);
            $table->dropIndex(['empresa_id', 'estado']);
        });
    }
};
