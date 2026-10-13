<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reemplaza los dos booleans sueltos (mostrar_modal_comprobante +
     * imprimir_automatico) por un único campo con 3 opciones claras —
     * "preguntar" (mostrar el cartel y, al confirmar, imprimir sin pasos de
     * más), "automatico" (imprimir derecho, sin preguntar nada) o
     * "sin_ticket" (no imprimir nada, como ya funcionaba antes). Los
     * booleans viejos NO se borran (se dejan de usar, nomás) — por las
     * dudas de necesitar volver atrás sin perder nada.
     */
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('comportamiento_impresion')->default('preguntar')->after('imprimir_automatico');
        });

        // Preserva el comportamiento actual de cada empresa tal cual estaba
        // configurado con los campos viejos, para que este cambio no le
        // mueva el piso a nadie.
        DB::table('empresas')->where('mostrar_modal_comprobante', false)
            ->update(['comportamiento_impresion' => 'sin_ticket']);

        DB::table('empresas')->where('mostrar_modal_comprobante', true)
            ->update(['comportamiento_impresion' => 'preguntar']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('comportamiento_impresion');
        });
    }
};
