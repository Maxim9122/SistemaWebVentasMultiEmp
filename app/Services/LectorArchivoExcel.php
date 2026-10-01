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

        return $this->recortarColumnasFantasma($hoja->toArray(null, true, true, false));
    }

    /**
     * Excel a veces reporta como "usado" un rango mucho más ancho de lo que
     * tiene datos reales (quedó un formato, borde o estilo aplicado sobre un
     * rango grande alguna vez, aunque esas celdas estén vacías) —
     * toArray() respeta ese rango tal cual, así que un archivo con 7
     * columnas de datos puede devolver 30, la mayoría vacías. Se recorta
     * desde el final hasta la última columna que tenga al menos un valor no
     * vacío en CUALQUIER fila (no solo el encabezado, por si hay una
     * columna sin título pero con datos reales debajo).
     *
     * @param  list<list<mixed>>  $filas
     * @return list<list<mixed>>
     */
    private function recortarColumnasFantasma(array $filas): array
    {
        if ($filas === []) {
            return $filas;
        }

        $ultimaColumnaConDatos = -1;

        foreach ($filas as $fila) {
            foreach ($fila as $indice => $valor) {
                if ($indice > $ultimaColumnaConDatos && trim((string) $valor) !== '') {
                    $ultimaColumnaConDatos = $indice;
                }
            }
        }

        if ($ultimaColumnaConDatos === -1) {
            return [];
        }

        return array_map(
            fn (array $fila) => array_slice($fila, 0, $ultimaColumnaConDatos + 1),
            $filas,
        );
    }
}
