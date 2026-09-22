<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExportadorProductosExcel
{
    /**
     * @param  Collection<int, \App\Models\Producto>  $productos
     */
    public function generar(Collection $productos, bool $incluirStock): Spreadsheet
    {
        $encabezados = ['Nombre', 'Código', 'Precio', 'Costo'];

        if ($incluirStock) {
            $encabezados[] = 'Stock';
        }

        array_push($encabezados, 'Categoría', 'Marca', 'Unidad', 'Proveedor');

        $filas = [$encabezados];

        foreach ($productos as $producto) {
            $fila = [
                $producto->nombre,
                $producto->codigo,
                (float) $producto->precio,
                $producto->costo !== null ? (float) $producto->costo : null,
            ];

            if ($incluirStock) {
                $fila[] = $producto->stock;
            }

            array_push($fila, $producto->categoria, $producto->marca, $producto->unidad, $producto->proveedor?->nombre);

            $filas[] = $fila;
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($filas, null, 'A1');

        foreach (range('A', $spreadsheet->getActiveSheet()->getHighestColumn()) as $columna) {
            $spreadsheet->getActiveSheet()->getColumnDimension($columna)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
