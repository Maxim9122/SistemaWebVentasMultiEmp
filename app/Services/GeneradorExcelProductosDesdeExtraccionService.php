<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Convierte la lista de productos ya editada por el usuario (extraída de una
 * foto) en un Spreadsheet real con los mismos encabezados que
 * MapeoColumnasService::CAMPOS — así, guardado como .xlsx y pasado por el
 * mismo flujo de importación por Excel, el mapeo de columnas se resuelve
 * solo (coincide exacto con un sinónimo) y el resto del pipeline (duplicados,
 * limpieza de números, historial) no necesita saber que el origen fue una
 * foto en vez de un archivo subido.
 *
 * A diferencia de ExportadorProductosExcel (que exporta productos ya
 * guardados, con precio/costo ya numéricos), acá los valores se dejan tal
 * cual los dejó el usuario en la pantalla de edición — es
 * ProductoImportController::confirmar() quien los limpia (normalizarNumero())
 * cuando de verdad procese el archivo, no hay que duplicar esa lógica acá.
 */
class GeneradorExcelProductosDesdeExtraccionService
{
    /**
     * @param  list<array<string, mixed>>  $productosEditados
     */
    public function generar(array $productosEditados): Spreadsheet
    {
        $filas = [MapeoColumnasService::CAMPOS];

        foreach ($productosEditados as $fila) {
            $filas[] = array_map(
                fn (string $campo) => $this->valorOVacio($fila[$campo] ?? null),
                MapeoColumnasService::CAMPOS,
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
