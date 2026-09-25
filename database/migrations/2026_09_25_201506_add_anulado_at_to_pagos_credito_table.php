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
        Schema::table('pagos_credito', function (Blueprint $table) {
            $table->timestamp('anulado_at')->nullable()->after('monto_transferencia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos_credito', function (Blueprint $table) {
            $table->dropColumn('anulado_at');
        });
    }
};
