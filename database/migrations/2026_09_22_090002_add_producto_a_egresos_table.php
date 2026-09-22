<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('egresos', function (Blueprint $table) {
            // Solo se completan cuando el motivo tiene requiere_producto=true.
            // precio_unitario_aplicado queda como constancia histórica del
            // precio realmente usado (con o sin descuento empleado vigente en
            // ese momento) — si después cambia el % configurado, no reescribe
            // egresos viejos.
            $table->foreignId('producto_id')->nullable()->after('motivo_egreso_id')->constrained('productos')->nullOnDelete();
            $table->unsignedInteger('cantidad')->nullable()->after('producto_id');
            $table->decimal('precio_unitario_aplicado', 12, 2)->nullable()->after('cantidad');
        });
    }

    public function down(): void
    {
        Schema::table('egresos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('producto_id');
            $table->dropColumn(['cantidad', 'precio_unitario_aplicado']);
        });
    }
};
