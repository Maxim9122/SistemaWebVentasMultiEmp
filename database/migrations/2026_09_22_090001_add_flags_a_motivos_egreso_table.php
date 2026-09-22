<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('motivos_egreso', function (Blueprint $table) {
            // Un motivo con esta marca (ej. "Consumos") pide producto+cantidad
            // en vez de un monto libre, descuenta stock igual que una venta, y
            // el buscador de beneficiario se restringe a staff (+ texto libre).
            $table->boolean('requiere_producto')->default(false)->after('activo');
            // Restringe el buscador de beneficiario a proveedores (+ texto
            // libre) — pensado para "Pago proveedores".
            $table->boolean('solo_proveedores')->default(false)->after('requiere_producto');
        });
    }

    public function down(): void
    {
        Schema::table('motivos_egreso', function (Blueprint $table) {
            $table->dropColumn(['requiere_producto', 'solo_proveedores']);
        });
    }
};
