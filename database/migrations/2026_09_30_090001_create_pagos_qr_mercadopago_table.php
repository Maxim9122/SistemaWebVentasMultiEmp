<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La "intención de pago" con QR: se crea ANTES de cobrar la venta (el
     * pedido se queda tal cual está mientras se espera que el cliente
     * escanee y pague) y recién cuando el webhook de Mercado Pago confirma
     * el pago se llama a ProcesadorDeCobro::cobrar() con este monto. Ver
     * PagoQrService.
     */
    public function up(): void
    {
        Schema::create('pagos_qr_mercadopago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pedido_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(); // cajero que lo generó — cobrar() lo necesita
            $table->decimal('monto', 12, 2);
            $table->string('preference_id')->nullable();
            $table->text('init_point')->nullable(); // URL de pago devuelta por MP, se convierte en QR
            $table->string('payment_id')->nullable()->index(); // lo llena el webhook
            $table->string('external_reference')->unique();
            $table->string('estado')->default('pendiente'); // pendiente | aprobado | rechazado | expirado | cancelado
            $table->timestamp('expira_at');

            // Todo lo que cobrar() necesita además del monto — se captura al
            // generar el QR (mismos datos que ya carga el cajero en el
            // formulario de cobro) porque el webhook que confirma el pago no
            // tiene sesión ni acceso al form original.
            $table->string('tipo_comprobante')->default('remito');
            $table->foreignId('cliente_id')->nullable()->constrained()->nullOnDelete();
            $table->string('cliente_nombre_nuevo')->nullable();
            $table->string('cliente_cuit_nuevo')->nullable();
            $table->string('cliente_telefono_nuevo')->nullable();
            $table->string('tipo_factura')->nullable();
            $table->json('datos_respuesta')->nullable(); // último payload de MP, para soporte/debug
            $table->timestamps();

            $table->index('pedido_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_qr_mercadopago');
    }
};
