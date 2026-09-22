<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExportadorClientesExcel
{
    /**
     * @param  Collection<int, \App\Models\Cliente>  $clientes
     */
    public function generar(Collection $clientes): Spreadsheet
    {
        $filas = [['Nombre', 'CUIT', 'Teléfono', 'Email']];

        foreach ($clientes as $cliente) {
            $filas[] = [$cliente->nombre, $cliente->cuit, $cliente->telefono, $cliente->email];
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($filas, null, 'A1');

        foreach (range('A', $spreadsheet->getActiveSheet()->getHighestColumn()) as $columna) {
            $spreadsheet->getActiveSheet()->getColumnDimension($columna)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
