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
     * Lee el archivo una sola vez y devuelve todo lo que hace falta de él:
     * encabezados, una vista previa chica, y todas las filas de datos
     * (sin el encabezado). A propósito un solo método que parsea una sola
     * vez — antes había un método separado para encabezados/preview y otro
     * para todas las filas, y cada controller de importación terminaba
     * llamando a uno u otro varias veces por separado (preview, contar
     * filas, detectar duplicados, detectar códigos inválidos), cada
     * llamada volviendo a parsear el Excel entero desde cero. Para un
     * archivo de ~7000 filas eso eran 4-5 parseos completos (~1.5-2s cada
     * uno, medido) en una sola importación — se sentía como que la pantalla
     * se quedaba trabada.
     *
     * $detectarFilaEncabezado (ver MapeoColumnasService::detectarFilaEncabezado)
     * recibe todas las filas crudas y devuelve el índice de la fila que es
     * el encabezado real — por defecto (sin pasar nada) se asume que es la
     * primera fila, como siempre. Queda afuera de esta clase a propósito:
     * decidir qué fila "parece" un encabezado de columnas necesita saber los
     * nombres de campos del dominio (nombre/código/precio, etc.), y este
     * lector es genérico y no sabe nada de eso.
     *
     * Algunas listas de precios repiten la fila de encabezado varias veces a
     * lo largo del archivo (una por cada categoría/sección: "ALIMENTOS",
     * fila de títulos, productos, "BAJA DE PRECIOS", fila de títulos nuevo,
     * más productos, etc.). Si solo se descartara la primera aparición, el
     * resto quedaría mezclado entre los datos como si fuera un producto más
     * (con "precio" literal "EFECTIVO" o como se llame esa columna) —
     * rompiendo esa fila en la importación. Por eso cualquier fila que
     * repita exactamente los mismos valores que el encabezado detectado se
     * descarta también, no solo la primera.
     *
     * @return array{encabezados: list<string>, preview: list<list<mixed>>, filas: list<list<mixed>>}
     */
    public function leer(string $rutaAbsoluta, ?callable $detectarFilaEncabezado = null): array
    {
        $todas = $this->leerTodasLasFilas($rutaAbsoluta);
        $indiceEncabezado = $detectarFilaEncabezado !== null ? $detectarFilaEncabezado($todas) : 0;
        $encabezados = array_map(fn ($valor) => trim((string) $valor), $todas[$indiceEncabezado] ?? []);

        $filas = array_values(array_filter(
            array_slice($todas, $indiceEncabezado + 1),
            fn (array $fila) => ! $this->esRepeticionDeEncabezado($fila, $encabezados),
        ));

        return [
            'encabezados' => array_values($encabezados),
            'preview' => array_slice($filas, 0, self::FILAS_PREVIEW),
            'filas' => $filas,
        ];
    }

    /**
     * @param  list<mixed>  $fila
     * @param  list<string>  $encabezados
     */
    private function esRepeticionDeEncabezado(array $fila, array $encabezados): bool
    {
        if ($encabezados === []) {
            return false;
        }

        $filaNormalizada = array_map(fn ($valor) => trim((string) $valor), array_slice($fila, 0, count($encabezados)));

        return $filaNormalizada === $encabezados;
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
