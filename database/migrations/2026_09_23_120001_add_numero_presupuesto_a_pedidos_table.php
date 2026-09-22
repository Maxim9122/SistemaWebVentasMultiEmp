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
            $table->unsignedInteger('numero_presupuesto')->nullable()->after('numero_venta');
            $table->unique(['empresa_id', 'numero_presupuesto']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropUnique(['empresa_id', 'numero_presupuesto']);
            $table->dropColumn('numero_presupuesto');
        });
    }
};
