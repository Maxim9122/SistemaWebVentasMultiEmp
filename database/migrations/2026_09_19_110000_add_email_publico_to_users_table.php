<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Separado a propósito de `email` (el de login): el de login nunca se
            // muestra en público (footer). Solo tiene sentido hoy para el
            // superadmin — ver Superadmin\PerfilController.
            $table->string('email_publico')->nullable()->after('telefono');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_publico');
        });
    }
};
