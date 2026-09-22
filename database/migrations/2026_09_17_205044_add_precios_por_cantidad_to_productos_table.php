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
        Schema::table('productos', function (Blueprint $table) {
            $table->unsignedInteger('cantidad_minima_1')->nullable()->after('es_promocion');
            $table->decimal('precio_cantidad_1', 12, 2)->nullable()->after('cantidad_minima_1');
            $table->unsignedInteger('cantidad_minima_2')->nullable()->after('precio_cantidad_1');
            $table->decimal('precio_cantidad_2', 12, 2)->nullable()->after('cantidad_minima_2');
            $table->unsignedInteger('cantidad_minima_3')->nullable()->after('precio_cantidad_2');
            $table->decimal('precio_cantidad_3', 12, 2)->nullable()->after('cantidad_minima_3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn([
                'cantidad_minima_1', 'precio_cantidad_1',
                'cantidad_minima_2', 'precio_cantidad_2',
                'cantidad_minima_3', 'precio_cantidad_3',
            ]);
        });
    }
};
