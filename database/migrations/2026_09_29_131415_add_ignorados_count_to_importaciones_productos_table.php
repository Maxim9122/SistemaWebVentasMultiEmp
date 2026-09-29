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
        Schema::table('importaciones_productos', function (Blueprint $table) {
            $table->unsignedInteger('ignorados_count')->default(0)->after('errores_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('importaciones_productos', function (Blueprint $table) {
            $table->dropColumn('ignorados_count');
        });
    }
};
