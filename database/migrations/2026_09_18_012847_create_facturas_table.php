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
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('cliente_nombre');
            $table->string('cliente_cuit')->nullable();
            $table->string('tipo_factura');

            // Placeholders para cuando se conecte la API de facturación ARCA/AFIP.
            $table->string('numero_comprobante')->nullable();
            $table->string('cae')->nullable();
            $table->date('cae_vencimiento')->nullable();
            $table->string('estado')->default('pendiente');

            $table->timestamps();
            $table->index('empresa_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
