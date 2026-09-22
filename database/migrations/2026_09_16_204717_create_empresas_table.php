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
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social');
            $table->string('cuit');
            $table->string('rubro')->nullable();
            $table->string('email_contacto');
            $table->string('telefono')->nullable();
            $table->string('direccion')->nullable();
            $table->enum('estado', ['pendiente', 'activa', 'rechazada', 'suspendida'])->default('pendiente');
            $table->text('motivo_rechazo')->nullable();
            $table->text('motivo_suspension')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
