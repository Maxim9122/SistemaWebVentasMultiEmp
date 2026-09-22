<?php

namespace App\Services\Facturacion;

class CalculadoraIva
{
    private const ID_ALICUOTA_GENERAL = 5;

    /**
     * Desarma un total que ya incluye IVA en neto + IVA. Asume alícuota
     * general 21% para todo — si en algún momento se venden productos
     * exentos o con otra alícuota, esto no alcanza y hay que discriminar
     * por ítem.
     *
     * @return array{importe_neto: float, importe_iva: float, importe_total: float}
     */
    public function calcular(float $importeTotal): array
    {
        $importeNeto = round($importeTotal / 1.21, 2);
        $importeIva = round($importeTotal - $importeNeto, 2);

        return [
            'importe_neto' => $importeNeto,
            'importe_iva' => $importeIva,
            'importe_total' => round($importeTotal, 2),
        ];
    }

    /**
     * @return list<array{Id: int, BaseImp: float, Importe: float}>
     */
    public function detalleParaComprobante(float $importeTotal): array
    {
        $calculo = $this->calcular($importeTotal);

        return [[
            'Id' => self::ID_ALICUOTA_GENERAL,
            'BaseImp' => $calculo['importe_neto'],
            'Importe' => $calculo['importe_iva'],
        ]];
    }

    /**
     * Un Monotributista no discrimina IVA ante AFIP (va incluido en su cuota
     * fija, no se declara por venta) — la Factura C no lleva desglose:
     * neto = total, iva = 0.
     *
     * @return array{importe_neto: float, importe_iva: float, importe_total: float}
     */
    public function sinDiscriminar(float $importeTotal): array
    {
        return [
            'importe_neto' => round($importeTotal, 2),
            'importe_iva' => 0.0,
            'importe_total' => round($importeTotal, 2),
        ];
    }
}
