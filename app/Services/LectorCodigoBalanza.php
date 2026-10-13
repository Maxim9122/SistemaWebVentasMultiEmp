<?php

namespace App\Services;

use App\Models\Empresa;

/**
 * Interpreta un código de barras "con peso o precio embebido" — el que
 * imprime una balanza comercial, donde los 13 dígitos no identifican un
 * producto fijo (cambian en cada pesada) sino que traen adentro un
 * código de producto corto + el precio o el peso ya calculado. El
 * formato exacto (en qué posición va cada cosa) varía según la marca y
 * la configuración de cada balanza, así que es 100% configurable por
 * empresa (ver Empresa::balanzaConfigurada()) en vez de asumir un único
 * estándar.
 *
 * A propósito nunca lanza excepciones ni rompe nada si el código no
 * encaja — devuelve null, y el caller sigue con la búsqueda normal como
 * si esto no existiera (ver CarritoController::buscarCodigoBalanza).
 */
class LectorCodigoBalanza
{
    /**
     * @return array{codigo_producto: string, valor: float, tipo: string}|null
     */
    public function parsear(Empresa $empresa, string $codigo): ?array
    {
        if (! $empresa->balanzaConfigurada()) {
            return null;
        }

        $codigo = trim($codigo);

        if (! ctype_digit($codigo) || strlen($codigo) !== 13) {
            return null;
        }

        if (! str_starts_with($codigo, (string) $empresa->balanza_prefijo)) {
            return null;
        }

        $codigoProducto = $this->extraerSegmento($codigo, $empresa->balanza_pos_codigo, $empresa->balanza_largo_codigo);
        $valorCrudo = $this->extraerSegmento($codigo, $empresa->balanza_pos_valor, $empresa->balanza_largo_valor);

        if ($codigoProducto === null || $valorCrudo === null || $codigoProducto === '' || ! ctype_digit($valorCrudo)) {
            return null;
        }

        return [
            'codigo_producto' => $codigoProducto,
            'valor' => ((int) $valorCrudo) / (10 ** $empresa->balanza_decimales_valor),
            'tipo' => $empresa->balanza_tipo_valor,
        ];
    }

    /**
     * Sustring defensivo: nunca lee fuera de los 13 dígitos (una
     * configuración cargada mal — posición + largo que se pasan del
     * final del código — no tira error, simplemente no matchea nada).
     */
    private function extraerSegmento(string $codigo, int $posicion, int $largo): ?string
    {
        if ($posicion < 0 || $largo <= 0 || $posicion + $largo > strlen($codigo)) {
            return null;
        }

        return substr($codigo, $posicion, $largo);
    }
}
