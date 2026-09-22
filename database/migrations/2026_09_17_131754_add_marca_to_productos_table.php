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
            $table->string('marca')->nullable()->after('categoria');
            $table->index(['empresa_id', 'categoria']);
            $table->index(['empresa_id', 'marca']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['empresa_id', 'categoria']);
            $table->dropIndex(['empresa_id', 'marca']);
            $table->dropColumn('marca');
        });
    }
};
