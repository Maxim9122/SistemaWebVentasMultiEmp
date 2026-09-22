<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // Antes, la función de horario/cupo de cajeros se activaba sola en
            // cuanto había al menos un turno cargado — el usuario pidió un
            // check explícito, así se puede precargar el horario sin activarlo
            // todavía, o apagarlo un rato sin perder los turnos ya cargados.
            $table->boolean('horario_laboral_habilitado')->default(false)->after('ip_permitida');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('horario_laboral_habilitado');
        });
    }
};
