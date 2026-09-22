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
        Schema::create('notas_credito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            // Única por factura: el alcance actual es acreditar el 100% de una
            // factura una sola vez (no notas de crédito parciales todavía).
            $table->foreignId('factura_id')->unique()->constrained('facturas')->cascadeOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('importe_acreditado', 10, 2);
            $table->text('motivo');

            $table->string('numero_comprobante')->nullable();
            $table->string('comprobante_externo_id')->nullable();
            $table->string('cae')->nullable();
            $table->date('cae_vencimiento')->nullable();
            $table->string('estado')->default('pendiente');
            $table->text('error_mensaje')->nullable();

            $table->timestamps();
            $table->index('empresa_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notas_credito');
    }
};
