<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Lee un Excel/CSV genérico fila por fila — no sabe nada de productos,
 * clientes ni de ningún dominio en particular (originalmente se llamaba
 * "ImportadorProductosExcel" pero no tenía nada específico de productos; se
 * renombró al reutilizarse para la importación de Clientes).
 */
class LectorArchivoExcel
{
    private const FILAS_PREVIEW = 5;

    /**
     * @return array{encabezados: list<string>, preview: list<list<mixed>>}
     */
    public function leerEncabezadosYPreview(string $rutaAbsoluta): array
    {
        $filas = $this->leerTodasLasFilas($rutaAbsoluta);
        $encabezados = array_map(fn ($valor) => trim((string) $valor), $filas[0] ?? []);

        return [
            'encabezados' => array_values($encabezados),
            'preview' => array_slice($filas, 1, self::FILAS_PREVIEW),
        ];
    }

    /**
     * Todas las filas de datos del archivo, sin el encabezado.
     *
     * @return list<list<mixed>>
     */
    public function leerFilas(string $rutaAbsoluta): array
    {
        return array_slice($this->leerTodasLasFilas($rutaAbsoluta), 1);
    }

    /**
     * @return list<list<mixed>>
     */
    private function leerTodasLasFilas(string $rutaAbsoluta): array
    {
        $hoja = IOFactory::load($rutaAbsoluta)->getActiveSheet();

        return $hoja->toArray(null, true, true, false);
    }
}
