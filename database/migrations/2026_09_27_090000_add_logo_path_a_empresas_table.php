<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // Ruta relativa dentro del disco 'public' (ej. "logos/3_abc123.png"),
            // no la URL completa — así nunca queda pisada si cambia el dominio.
            // Nullable: la mayoría de las empresas no va a subir uno, y el
            // sidebar se ve bien sin logo (solo el nombre del sitio).
            $table->string('logo_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
