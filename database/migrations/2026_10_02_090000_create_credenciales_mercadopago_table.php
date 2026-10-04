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
        Schema::create('credenciales_mercadopago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('ambiente'); // sandbox | produccion
            $table->unsignedBigInteger('mp_user_id')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expira_en')->nullable();
            $table->string('estado')->default('pendiente_onboarding');
            // QR fijo: cada PUT de orden se dirige por {user_id}/{external_pos_id}
            // en la URL, hace falta tener estos ids guardados, no alcanza con el token.
            $table->string('mp_store_id')->nullable();
            $table->string('mp_pos_id')->nullable();
            $table->string('mp_external_store_id')->nullable();
            $table->string('mp_external_pos_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credenciales_mercadopago');
    }
};
