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
        Schema::table('empresas', function (Blueprint $table) {
            $table->boolean('permite_multiples_carritos')->default(false)->after('estado');
            $table->boolean('permite_cambiar_precio_venta')->default(false)->after('permite_multiples_carritos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['permite_multiples_carritos', 'permite_cambiar_precio_venta']);
        });
    }
};
