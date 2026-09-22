<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egresos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            // Un egreso siempre pertenece a una caja abierta — no tiene
            // sentido un egreso "suelto" fuera de un turno.
            $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete();
            // Quién lo cargó (normalmente el mismo dueño de la caja, pero se
            // guarda aparte por si en algún momento el admin carga uno).
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('motivo_egreso_id')->constrained('motivos_egreso');
            // Beneficiario: como mucho uno de estos tres tiene datos (proveedor
            // registrado, staff registrado, o nombre libre para alguien que no
            // está en el sistema) — todos nullable, ninguno obligatorio.
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->foreignId('beneficiario_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('beneficiario_nombre')->nullable();
            $table->decimal('monto_efectivo', 12, 2)->default(0);
            $table->decimal('monto_transferencia', 12, 2)->default(0);
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'created_at']);
            $table->index('caja_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egresos');
    }
};
