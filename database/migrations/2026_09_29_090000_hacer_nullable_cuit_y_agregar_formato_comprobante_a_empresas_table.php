<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El CUIT deja de ser obligatorio al registrar una empresa: solo hace falta
 * si la empresa va a facturar (se exige recién al configurar la condición
 * fiscal, ver ConfiguracionEmpresaController::update()). SQL crudo para
 * modificar la columna, mismo criterio que
 * 2026_09_25_100000_hacer_nullable_cuit_en_clientes_table (no sumar
 * doctrine/dbal solo por esto).
 *
 * `formato_comprobante` es independiente de `comprobante_predeterminado`
 * (que elige remito/factura A/B/C): este elige el LAYOUT de impresión —
 * ticket angosto (como hoy) o A4 — y aplica por igual a ventas,
 * presupuestos y comprobantes fiados.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE empresas MODIFY cuit VARCHAR(20) NULL');

        Schema::table('empresas', function (Blueprint $table) {
            $table->string('formato_comprobante')->default('ticket')->after('comprobante_predeterminado');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('formato_comprobante');
        });

        DB::statement("UPDATE empresas SET cuit = '' WHERE cuit IS NULL");
        DB::statement('ALTER TABLE empresas MODIFY cuit VARCHAR(20) NOT NULL');
    }
};
