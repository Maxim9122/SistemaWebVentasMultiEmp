<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sesiones_usuario', function (Blueprint $table) {
            // Se actualiza en cada request autenticado (ver CerrarSesionPorInactividad).
            // Sirve tanto para el cierre automático por inactividad como para saber,
            // desde otra request, si esta sesión sigue "ocupando cupo" ahora mismo.
            $table->timestamp('ultima_actividad_at')->nullable()->after('login_at');
            // Distingue un intento de acceso rechazado (fuera de horario, IP no
            // autorizada, cupo de cajeros lleno) de una sesión real — así queda
            // registrado en el mismo historial sin mezclarse con logins válidos.
            $table->boolean('bloqueada')->default(false)->after('ip_address');
            $table->string('motivo_bloqueo')->nullable()->after('bloqueada');
        });
    }

    public function down(): void
    {
        Schema::table('sesiones_usuario', function (Blueprint $table) {
            $table->dropColumn(['ultima_actividad_at', 'bloqueada', 'motivo_bloqueo']);
        });
    }
};
