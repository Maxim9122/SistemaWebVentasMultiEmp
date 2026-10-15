<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rastro de que la empresa aceptó los Términos y Condiciones al
     * registrarse (ver RegistroEmpresaController) — queda en null para
     * las empresas que ya estaban registradas antes de este cambio, a
     * propósito: no se las fuerza a re-aceptar nada de un día para el
     * otro, esto solo aplica hacia adelante.
     */
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->timestamp('terminos_aceptados_at')->nullable()->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('terminos_aceptados_at');
        });
    }
};
