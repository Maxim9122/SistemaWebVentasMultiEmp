<?php

namespace App\Services\MercadoPago;

use App\Models\CredencialMercadoPago;

/**
 * Orquesta el alta única de Sucursal + Caja (POS) en Mercado Pago — se
 * corre una sola vez por empresa, desde Configuración. El QR físico para
 * esa Caja se imprime una sola vez y no vuelve a generarse después de esto.
 *
 * Una sola Caja por empresa por ahora (alcance actual) — si en el futuro
 * hace falta más de un punto de cobro por empresa, acá es donde habría que
 * sumar un índice al external_id.
 */
class MercadoPagoCuentaService
{
    public function __construct(private readonly MercadoPagoApiClient $cliente) {}

    /**
     * @param  array{street_number: string, street_name: string, city_name: string, state_name: string, latitude: float, longitude: float, reference: string}  $ubicacion
     */
    public function crearStoreYPos(CredencialMercadoPago $credencial, string $nombreEmpresa, array $ubicacion): CredencialMercadoPago
    {
        // El external_id de la Sucursal acepta guiones, pero el de la Caja
        // NO (confirmado contra el sandbox real: "does not meet the
        // expected format" con guiones, funciona solo alfanumérico) — por
        // eso van con formatos distintos acá, no es un descuido.
        $externalStoreId = "empresa-{$credencial->empresa_id}";
        $externalPosId = "empresa{$credencial->empresa_id}caja1";

        $store = $this->cliente->crearStore($credencial->access_token, $credencial->mp_user_id, [
            'name' => $nombreEmpresa,
            'external_id' => $externalStoreId,
            'location' => $ubicacion,
        ]);

        // Solo store_id acá (no external_store_id): justo después de crear
        // la Sucursal, Mercado Pago a veces todavía no propagó el
        // external_id para poder buscarla por ahí ("External store id does
        // not refer any store", confirmado contra el sandbox real) — el
        // store_id numérico, en cambio, ya es válido al instante porque es
        // el mismo que acaba de devolver la creación de la Sucursal.
        $pos = $this->cliente->crearPos($credencial->access_token, [
            'name' => 'Caja 1',
            'store_id' => $store['id'],
            'external_id' => $externalPosId,
            'config' => [
                'qr' => [
                    // "pdv" = modo atendido: nosotros creamos la orden con
                    // el monto exacto por API antes de que el cliente
                    // escanee. "standalone"/"dynamic" no aplican acá.
                    'operating_mode' => 'pdv',
                ],
            ],
        ]);

        $credencial->update([
            'mp_store_id' => (string) $store['id'],
            'mp_pos_id' => (string) $pos['id'],
            'mp_external_store_id' => $externalStoreId,
            'mp_external_pos_id' => $externalPosId,
            // La propia respuesta de crear la Caja ya trae el QR fijo listo
            // para imprimir — no hace falta generarlo nosotros.
            'mp_qr_image_url' => $pos['qr_response']['image'] ?? null,
            'mp_store_location' => $ubicacion,
        ]);

        return $credencial->fresh();
    }
}
