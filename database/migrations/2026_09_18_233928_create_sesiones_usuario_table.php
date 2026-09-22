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
        Schema::create('sesiones_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Nullable/nullOnDelete igual que users.empresa_id — un superadmin no
            // tiene empresa, y así una empresa borrada no arrastra el historial.
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->timestamp('login_at');
            // Null = no se registró un cierre explícito (sesión expirada, navegador
            // cerrado sin logout, etc.) — no necesariamente sigue activa ahora mismo.
            $table->timestamp('logout_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
            $table->index(['empresa_id', 'login_at']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesiones_usuario');
    }
};
