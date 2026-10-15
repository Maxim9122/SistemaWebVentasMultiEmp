<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExportadorProductosExcel
{
    /**
     * El stock se incluye siempre — a diferencia de `controla_stock` (que
     * decide si el sistema valida/descuenta stock en las ventas), el campo
     * en sí siempre existe en el producto, y varias empresas lo usan como
     * referencia manual para controlar mercadería aunque no lo tengan
     * activado para las ventas. Ver Producto::stock (nullable).
     *
     * @param  Collection<int, \App\Models\Producto>  $productos
     */
    public function generar(Collection $productos): Spreadsheet
    {
        $encabezados = ['Nombre', 'Código', 'Precio', 'Costo', 'Stock', 'Categoría', 'Marca', 'Unidad', 'Proveedor'];

        $filas = [$encabezados];

        foreach ($productos as $producto) {
            $filas[] = [
                $producto->nombre,
                $producto->codigo,
                (float) $producto->precio,
                $producto->costo !== null ? (float) $producto->costo : null,
                $producto->stock,
                $producto->categoria,
                $producto->marca,
                $producto->unidad,
                $producto->proveedor?->nombre,
            ];
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($filas, null, 'A1');

        foreach (range('A', $spreadsheet->getActiveSheet()->getHighestColumn()) as $columna) {
            $spreadsheet->getActiveSheet()->getColumnDimension($columna)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
