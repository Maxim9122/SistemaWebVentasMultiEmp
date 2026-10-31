<?php

namespace App\Services;

class NormalizadorNumeroService
{
    /**
     * Limpia un valor numérico tal cual puede venir de una fuente externa
     * (Excel, foto leída por IA, etc.): con símbolo de moneda ("$100"),
     * espacios ("$ 1.500"), separador de miles y/o decimales en formato
     * argentino ("$1.500,50") o en formato en inglés ("1,500.50"). Devuelve
     * un string que is_numeric() / (float) puedan procesar bien, o null si
     * no se pudo interpretar como número.
     *
     * Para decidir qué es separador de miles y qué es decimal cuando el
     * valor solo trae UNO de los dos símbolos (ej "1.500" o "1,500", sin el
     * otro para desambiguar): si tiene exactamente 3 dígitos después del
     * símbolo, se asume separador de miles (así "1.500" da 1500, no 1.5);
     * con 1 o 2 dígitos, se asume decimal (así "99,90" da 99.90).
     */
    public function normalizar(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        // Saca cualquier cosa que no sea dígito, coma, punto o signo menos
        // (símbolo de moneda, espacios, "ARS", etc.).
        $valor = preg_replace('/[^\d,.\-]/', '', $valor) ?? '';

        if ($valor === '' || $valor === '-') {
            return null;
        }

        $tienePunto = str_contains($valor, '.');
        $tieneComa = str_contains($valor, ',');

        if ($tienePunto && $tieneComa) {
            if (strrpos($valor, ',') > strrpos($valor, '.')) {
                $valor = str_replace(',', '.', str_replace('.', '', $valor));
            } else {
                $valor = str_replace(',', '', $valor);
            }
        } elseif ($tieneComa) {
            $valor = strlen(substr($valor, strrpos($valor, ',') + 1)) === 3
                ? str_replace(',', '', $valor)
                : str_replace(',', '.', $valor);
        } elseif ($tienePunto) {
            if (strlen(substr($valor, strrpos($valor, '.') + 1)) === 3) {
                $valor = str_replace('.', '', $valor);
            }
        }

        return is_numeric($valor) ? $valor : null;
    }
}
