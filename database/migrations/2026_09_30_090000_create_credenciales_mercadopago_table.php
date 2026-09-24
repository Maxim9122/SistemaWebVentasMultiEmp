<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credenciales_mercadopago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('ambiente'); // homologacion | produccion
            $table->unsignedBigInteger('mp_user_id')->nullable(); // id de la cuenta MP conectada, informativo
            // text (no string): el cast `encrypted` produce ciphertext largo,
            // mismo criterio que credenciales_facturacion.api_key.
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expira_en')->nullable();
            $table->string('estado')->default('pendiente_onboarding'); // pendiente_onboarding | activa
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credenciales_mercadopago');
    }
};
