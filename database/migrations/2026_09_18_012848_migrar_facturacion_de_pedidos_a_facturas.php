<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $pedidos = DB::table('pedidos')
            ->where('tipo_comprobante', 'factura')
            ->whereNotNull('cliente_id')
            ->get();

        foreach ($pedidos as $pedido) {
            $cliente = DB::table('clientes')->where('id', $pedido->cliente_id)->first();

            $facturaId = DB::table('facturas')->insertGetId([
                'empresa_id' => $pedido->empresa_id,
                'cliente_id' => $pedido->cliente_id,
                'cliente_nombre' => $cliente->nombre ?? 'Consumidor no identificado',
                'cliente_cuit' => $cliente->cuit ?? null,
                'tipo_factura' => $pedido->tipo_factura ?? 'C',
                'estado' => 'pendiente',
                'created_at' => $pedido->cobrado_at ?? now(),
                'updated_at' => $pedido->cobrado_at ?? now(),
            ]);

            DB::table('pedidos')->where('id', $pedido->id)->update(['factura_id' => $facturaId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op deliberado: revertir movería datos a columnas que la migración
        // siguiente ya elimina. Si hace falta deshacer toda la feature, restaurar desde backup.
    }
};
