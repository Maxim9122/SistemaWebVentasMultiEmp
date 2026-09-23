<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Solo tiene sentido en el usuario superadmin (el ícono es del
            // sitio entero, no por empresa) — se guarda acá en vez de una
            // tabla de configuración nueva porque el perfil de superadmin ya
            // es donde viven los otros datos "del sitio" (telefono,
            // email_publico, usados en el pie de los PDF). Nullable: sin
            // configurar, se usa el favicon.ico estático de siempre.
            $table->string('icono_sitio_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('icono_sitio_path');
        });
    }
};
