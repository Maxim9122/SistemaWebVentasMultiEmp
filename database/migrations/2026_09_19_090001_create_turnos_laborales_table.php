<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnos_laborales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            // 1=lunes .. 7=domingo (Carbon::dayOfWeekIso). Cada fila es un turno
            // (un rango horario) para ese día — un día puede tener hasta 2 filas.
            $table->unsignedTinyInteger('dia_semana');
            $table->time('hora_desde');
            $table->time('hora_hasta');
            // Null = sin límite. Solo aplica a cajero/cajero_vendedor (ver
            // ControlAccesoStaff) — a propósito no hay límite de vendedores.
            $table->unsignedInteger('limite_cajeros')->nullable();
            $table->timestamps();
            $table->index(['empresa_id', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnos_laborales');
    }
};
