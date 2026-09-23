<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El "lote" de una reposición de stock — mismo criterio que
     * importaciones_productos: un evento con quién/cuándo/de dónde, y el
     * detalle producto por producto va en la tabla hija
     * (reposicion_stock_items). `revertida_at` mismo patrón que las
     * importaciones, para poder deshacer un lote entero.
     */
    public function up(): void
    {
        Schema::create('reposiciones_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->text('nota')->nullable();
            $table->timestamp('revertida_at')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reposiciones_stock');
    }
};
