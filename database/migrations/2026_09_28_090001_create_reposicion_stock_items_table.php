<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `nombre_producto` es una copia (igual que `PedidoItem::nombre_producto`
     * o `Factura::cliente_nombre` en este mismo proyecto) — si el producto
     * se termina borrando de verdad más adelante, el historial de esta
     * reposición sigue siendo legible.
     */
    public function up(): void
    {
        Schema::create('reposicion_stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reposicion_id')->constrained('reposiciones_stock')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->string('nombre_producto');
            $table->integer('cantidad_anterior');
            $table->integer('cantidad_agregada');
            $table->integer('cantidad_nueva');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reposicion_stock_items');
    }
};
