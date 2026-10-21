<?php

namespace App\Services;

use App\Models\AjustePrecioBusqueda;
use App\Models\Producto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lógica compartida para aplicar un ajuste de precio por % a un lote de
 * productos, con su registro de historial/deshacer (AjustePrecioBusqueda +
 * AjustePrecioBusquedaCambio) — extraída de
 * ProductoController::ajustarPrecioBusqueda() para poder reusarla desde
 * otros flujos (ver ReposicionStockController, que la ofrece junto con
 * reponer stock desde una foto) sin duplicar la cuenta ni el historial.
 */
class AjustePrecioService
{
    /**
     * @param  Collection<int, Producto>  $productos
     */
    public function aplicar(Collection $productos, float $porcentaje, int $empresaId, int $userId, string $descripcionFiltro): AjustePrecioBusqueda
    {
        return DB::transaction(function () use ($productos, $porcentaje, $empresaId, $userId, $descripcionFiltro) {
            $ajuste = AjustePrecioBusqueda::create([
                'empresa_id' => $empresaId,
                'user_id' => $userId,
                'porcentaje' => $porcentaje,
                'descripcion_filtro' => $descripcionFiltro,
                'productos_count' => $productos->count(),
            ]);

            foreach ($productos as $producto) {
                $precioAnterior = (float) $producto->precio;
                $nuevoPrecio = max(0, round($precioAnterior * (1 + $porcentaje / 100), 2));

                $producto->update(['precio' => $nuevoPrecio]);

                $ajuste->cambios()->create([
                    'producto_id' => $producto->id,
                    'precio_anterior' => $precioAnterior,
                    'precio_nuevo' => $nuevoPrecio,
                ]);
            }

            return $ajuste;
        });
    }
}
