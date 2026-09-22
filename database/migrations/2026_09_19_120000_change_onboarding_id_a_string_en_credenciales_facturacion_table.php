<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Estaba como unsignedBigInteger asumiendo que la API de facturación
        // siempre manda un id numérico — la propia spec del proyecto hermano
        // no lo garantiza (mismo motivo por el que comprobante_externo_id ya
        // es string en `facturas`). Con un id no numérico, el webhook de
        // onboarding tiraba un 500 real (PDOException) en vez de guardar el
        // dato — grave porque es el webhook que activa la facturación
        // electrónica real de una empresa. SQL crudo en vez de ->change()
        // para no sumar doctrine/dbal solo por este cambio puntual.
        DB::statement('ALTER TABLE credenciales_facturacion MODIFY onboarding_id VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE credenciales_facturacion MODIFY onboarding_id BIGINT UNSIGNED NULL');
    }
};
