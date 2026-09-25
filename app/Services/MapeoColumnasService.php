<?php

namespace App\Services;

/**
 * Sugiere a qué campo del sistema corresponde cada columna de un Excel
 * importado, comparando el encabezado (normalizado) contra un diccionario de
 * sinónimos — sin IA, a propósito (alcanza y no suma costo/latencia/
 * dependencias). `CAMPOS`/`SINONIMOS` son el esquema de Productos (el
 * primero en usar esto); para otro tipo de importación (ver
 * `MapeoColumnasClienteService`) se hereda esta clase y se pisan esas dos
 * constantes — el algoritmo de acá (normalizar/sugerir por sinónimo exacto/
 * sugerir por similitud) es 100% genérico y no sabe nada de productos.
 */
class MapeoColumnasService
{
    public const CAMPOS = ['nombre', 'codigo', 'precio', 'costo', 'stock', 'categoria', 'marca', 'unidad'];

    protected const SINONIMOS = [
        'nombre' => ['nombre', 'producto', 'articulo', 'descripcion', 'detalle', 'item', 'denominacion'],
        'codigo' => ['codigo', 'cod', 'sku', 'cod barras', 'codigo barras', 'ean', 'codigo producto'],
        'precio' => ['precio', 'precio venta', 'pvp', 'precio unitario', 'precio de venta', 'precio publico'],
        'costo' => ['costo', 'precio costo', 'precio compra', 'costo unitario', 'costo de compra'],
        'stock' => ['stock', 'cantidad', 'existencia', 'stock actual', 'cant'],
        'categoria' => ['categoria', 'rubro', 'familia', 'grupo', 'linea'],
        'marca' => ['marca', 'fabricante', 'brand'],
        'unidad' => ['unidad', 'um', 'unidad medida', 'unidad de medida', 'presentacion'],
    ];

    /**
     * Sugiere, para cada encabezado del Excel (por índice), a qué campo del
     * sistema corresponde. $mapeoRecordado es el mapeo guardado de la
     * empresa (encabezado normalizado => campo), que tiene prioridad sobre
     * el diccionario de sinónimos.
     *
     * @param  list<string>  $encabezados
     * @param  array<string, string>|null  $mapeoRecordado
     * @return array<int, string|null>
     */
    public function sugerir(array $encabezados, ?array $mapeoRecordado = null): array
    {
        $sugerencias = [];
        $usados = [];

        foreach ($encabezados as $indice => $encabezado) {
            $normalizado = $this->normalizar((string) $encabezado);

            $campo = $mapeoRecordado[$normalizado]
                ?? $this->buscarPorSinonimoExacto($normalizado)
                ?? $this->buscarPorSimilitud($normalizado);

            if ($campo !== null && in_array($campo, $usados, true)) {
                $campo = null;
            }

            if ($campo !== null) {
                $usados[] = $campo;
            }

            $sugerencias[$indice] = $campo;
        }

        return $sugerencias;
    }

    /**
     * Expone el diccionario de sinónimos tal cual (solo lectura) para
     * mostrarlo como referencia en pantalla (ver "Guía rápida" en
     * productos/importar/subir.blade.php) — evita mantener una copia
     * duplicada del diccionario en la vista que se pueda desincronizar.
     *
     * @return array<string, list<string>>
     */
    public static function sinonimosPorCampo(): array
    {
        return static::SINONIMOS;
    }

    public function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u',
        ]);
        $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto) ?? '';

        return trim($texto);
    }

    private function buscarPorSinonimoExacto(string $normalizado): ?string
    {
        if ($normalizado === '') {
            return null;
        }

        foreach (static::SINONIMOS as $campo => $sinonimos) {
            if (in_array($normalizado, $sinonimos, true)) {
                return $campo;
            }
        }

        return null;
    }

    private function buscarPorSimilitud(string $normalizado): ?string
    {
        if ($normalizado === '') {
            return null;
        }

        $mejorCampo = null;
        $mejorPorcentaje = 0.0;

        foreach (static::SINONIMOS as $campo => $sinonimos) {
            foreach ($sinonimos as $sinonimo) {
                similar_text($normalizado, $sinonimo, $porcentaje);

                if ($porcentaje > $mejorPorcentaje) {
                    $mejorPorcentaje = $porcentaje;
                    $mejorCampo = $campo;
                }
            }
        }

        return $mejorPorcentaje >= 80.0 ? $mejorCampo : null;
    }
}
