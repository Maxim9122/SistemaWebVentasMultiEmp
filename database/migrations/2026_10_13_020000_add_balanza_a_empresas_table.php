<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configuración (apagada por defecto) para que el sistema reconozca el
     * código de barras "con peso o precio embebido" que imprimen las
     * balanzas comerciales — cada balanza/marca arma esos 13 dígitos
     * distinto, así que se deja 100% configurable en vez de asumir un
     * único formato fijo.
     */
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->boolean('balanza_activa')->default(false)->after('comportamiento_impresion');
            $table->string('balanza_prefijo', 4)->nullable()->after('balanza_activa');
            $table->unsignedTinyInteger('balanza_pos_codigo')->nullable()->after('balanza_prefijo');
            $table->unsignedTinyInteger('balanza_largo_codigo')->nullable()->after('balanza_pos_codigo');
            $table->unsignedTinyInteger('balanza_pos_valor')->nullable()->after('balanza_largo_codigo');
            $table->unsignedTinyInteger('balanza_largo_valor')->nullable()->after('balanza_pos_valor');
            $table->unsignedTinyInteger('balanza_decimales_valor')->default(2)->after('balanza_largo_valor');
            $table->string('balanza_tipo_valor', 10)->nullable()->after('balanza_decimales_valor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'balanza_activa', 'balanza_prefijo', 'balanza_pos_codigo', 'balanza_largo_codigo',
                'balanza_pos_valor', 'balanza_largo_valor', 'balanza_decimales_valor', 'balanza_tipo_valor',
            ]);
        });
    }
};
