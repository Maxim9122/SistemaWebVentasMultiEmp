<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Mismo criterio que GeneradorExcelProductosDesdeExtraccionService, para el
 * esquema de Clientes (MapeoColumnasClienteService::CAMPOS) en vez de
 * Productos — ver ese archivo para el razonamiento completo.
 */
class GeneradorExcelClientesDesdeExtraccionService
{
    /**
     * @param  list<array<string, mixed>>  $clientesEditados
     */
    public function generar(array $clientesEditados): Spreadsheet
    {
        $filas = [MapeoColumnasClienteService::CAMPOS];

        foreach ($clientesEditados as $fila) {
            $filas[] = array_map(
                fn (string $campo) => $this->valorOVacio($fila[$campo] ?? null),
                MapeoColumnasClienteService::CAMPOS,
            );
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($filas, null, 'A1');

        return $spreadsheet;
    }

    private function valorOVacio(mixed $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }
}
