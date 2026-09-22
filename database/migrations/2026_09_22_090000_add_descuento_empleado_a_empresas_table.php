<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // Null = sin descuento configurado, se usa el precio normal del
            // catálogo. Se aplica solo a egresos de tipo "consumo interno".
            $table->decimal('descuento_precio_empleado_porcentaje', 5, 2)->nullable()->after('ip_permitida');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('descuento_precio_empleado_porcentaje');
        });
    }
};
