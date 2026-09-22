<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // Null = sin restricción de IP. Aplica solo a cajero/vendedor/cajero_vendedor,
            // el admin queda siempre exento (se resuelve en ControlAccesoStaff).
            $table->string('ip_permitida')->nullable()->after('ultimo_numero_venta');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('ip_permitida');
        });
    }
};
