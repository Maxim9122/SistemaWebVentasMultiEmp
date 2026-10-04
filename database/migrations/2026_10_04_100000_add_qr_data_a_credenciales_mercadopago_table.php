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
        Schema::table('credenciales_mercadopago', function (Blueprint $table) {
            // La API de Mercado Pago ya devuelve el QR fijo listo para
            // mostrar al crear la Caja (campo qr_response.image) — se
            // guarda acá para no tener que autogenerarlo.
            $table->text('mp_qr_image_url')->nullable()->after('mp_external_pos_id');
            // location es obligatorio para crear la Sucursal en Mercado
            // Pago (street_number, street_name, city_name, state_name,
            // latitude, longitude, reference) — se guarda como JSON porque
            // es específico de esta integración, no se usa en otro lado.
            $table->json('mp_store_location')->nullable()->after('mp_qr_image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credenciales_mercadopago', function (Blueprint $table) {
            $table->dropColumn(['mp_qr_image_url', 'mp_store_location']);
        });
    }
};
