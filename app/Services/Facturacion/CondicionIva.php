<?php

namespace App\Services\Facturacion;

/**
 * Condición del cliente frente al IVA, para mandarla como
 * `cliente_condicion_iva` al emitir un comprobante (campo opcional de la
 * API de facturación — RG 5616 de AFIP). Si no se manda, la API sigue
 * usando su comportamiento de siempre (Factura A fuerza Responsable
 * Inscripto igual; B/C quedan en Consumidor Final).
 */
class CondicionIva
{
    public const OPCIONES = [
        'responsable_inscripto' => 'Responsable Inscripto',
        'monotributista' => 'Monotributista',
        'exento' => 'Exento',
        'consumidor_final' => 'Consumidor Final',
        'sujeto_no_categorizado' => 'Sujeto No Categorizado',
        'cliente_exterior' => 'Cliente del Exterior',
        'iva_no_alcanzado' => 'IVA No Alcanzado',
    ];

    private const MAPA_AFIP = [
        'responsable_inscripto' => 1,
        'exento' => 4,
        'consumidor_final' => 5,
        'monotributista' => 6,
        'sujeto_no_categorizado' => 7,
        'cliente_exterior' => 9,
        'iva_no_alcanzado' => 15,
    ];

    public static function codigoAfip(?string $clave): ?int
    {
        return self::MAPA_AFIP[$clave] ?? null;
    }
}
