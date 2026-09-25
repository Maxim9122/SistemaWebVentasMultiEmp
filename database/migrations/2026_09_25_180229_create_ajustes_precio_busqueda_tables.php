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
        Schema::create('ajustes_precio_busqueda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('porcentaje', 8, 2);
            $table->string('descripcion_filtro');
            $table->unsignedInteger('productos_count')->default(0);
            $table->timestamp('revertido_at')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'created_at']);
        });

        Schema::create('ajuste_precio_busqueda_cambios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ajuste_id')->constrained('ajustes_precio_busqueda')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->decimal('precio_anterior', 12, 2);
            $table->decimal('precio_nuevo', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ajuste_precio_busqueda_cambios');
        Schema::dropIfExists('ajustes_precio_busqueda');
    }
};
