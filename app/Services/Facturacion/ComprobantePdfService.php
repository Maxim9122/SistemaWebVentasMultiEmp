<?php

namespace App\Services\Facturacion;

use App\Models\Empresa;
use App\Models\Factura;
use App\Models\NotaCredito;
use App\Models\Pedido;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Carbon;

/**
 * Genera el PDF del comprobante (ticket angosto, mismo formato que se
 * imprime en el local) a partir de una Factura, una NotaCredito ya aprobada
 * (con CAE), o un Remito. Solo arma el documento — nunca llama a la API de
 * facturación ni decide estados.
 */
class ComprobantePdfService
{
    private const CODIGO_FACTURA = [
        'A' => '001',
        'B' => '006',
        'C' => '011',
    ];

    private const CODIGO_NOTA_CREDITO = [
        'A' => '003',
        'B' => '008',
        'C' => '013',
    ];

    public function __construct(private readonly CalculadoraIva $iva) {}

    public function generarFactura(Factura $factura, Pedido $pedido): string
    {
        return $this->generar(
            empresa: $factura->empresa,
            pedido: $pedido,
            letra: $factura->tipo_factura,
            tituloComprobante: 'Factura '.$factura->tipo_factura,
            codigoAfip: self::CODIGO_FACTURA[$factura->tipo_factura] ?? '000',
            numeroComprobante: $factura->numero_comprobante,
            cae: $factura->cae,
            caeVencimiento: $factura->cae_vencimiento,
            fechaEmision: $pedido->cobrado_at,
            clienteNombre: $factura->cliente_nombre,
            clienteCuit: $factura->cliente_cuit,
            importe: (float) $pedido->total_cobrado,
        );
    }

    public function generarNotaCredito(NotaCredito $notaCredito, Pedido $pedido): string
    {
        $factura = $notaCredito->factura;

        return $this->generar(
            empresa: $notaCredito->empresa,
            pedido: $pedido,
            letra: $factura->tipo_factura,
            tituloComprobante: 'Nota de Crédito '.$factura->tipo_factura,
            codigoAfip: self::CODIGO_NOTA_CREDITO[$factura->tipo_factura] ?? '000',
            numeroComprobante: $notaCredito->numero_comprobante,
            cae: $notaCredito->cae,
            caeVencimiento: $notaCredito->cae_vencimiento,
            fechaEmision: $notaCredito->updated_at,
            clienteNombre: $factura->cliente_nombre,
            clienteCuit: $factura->cliente_cuit,
            importe: (float) $notaCredito->importe_acreditado,
            referenciaAsociada: "Anula Factura {$factura->tipo_factura} Nro {$factura->numero_comprobante}",
        );
    }

    public function generarRemito(Pedido $pedido): string
    {
        $empresa = $pedido->empresa;
        $html = view($this->vista($empresa, 'remito-pdf'), [
            'empresa' => $empresa,
            'pedido' => $pedido,
        ])->render();

        return $this->renderizar($html, $pedido->items->count(), 0, $empresa->usaFormatoA4());
    }

    public function generarPresupuesto(Pedido $pedido): string
    {
        $empresa = $pedido->empresa;
        $html = view($this->vista($empresa, 'presupuesto-pdf'), [
            'empresa' => $empresa,
            'pedido' => $pedido,
        ])->render();

        return $this->renderizar($html, $pedido->items->count(), 0, $empresa->usaFormatoA4());
    }

    private function generar(
        Empresa $empresa,
        Pedido $pedido,
        string $letra,
        string $tituloComprobante,
        string $codigoAfip,
        ?string $numeroComprobante,
        ?string $cae,
        ?Carbon $caeVencimiento,
        ?Carbon $fechaEmision,
        string $clienteNombre,
        ?string $clienteCuit,
        float $importe,
        ?string $referenciaAsociada = null,
    ): string {
        $credencial = $empresa->credencialFacturacion;
        $puntoVenta = $credencial?->punto_venta;
        $calculo = $letra === 'C' ? $this->iva->sinDiscriminar($importe) : $this->iva->calcular($importe);

        $qr = ($cae && $numeroComprobante && $puntoVenta && $fechaEmision)
            ? $this->qrAfip($empresa, (int) $codigoAfip, $puntoVenta, $numeroComprobante, $cae, $fechaEmision, $importe, $clienteCuit)
            : null;

        $html = view($this->vista($empresa, 'comprobante-pdf'), [
            'empresa' => $empresa,
            'pedido' => $pedido,
            'letra' => $letra,
            'tituloComprobante' => $tituloComprobante,
            'codigoAfip' => $codigoAfip,
            'puntoVenta' => $puntoVenta ? str_pad((string) $puntoVenta, 4, '0', STR_PAD_LEFT) : '----',
            'numeroComprobante' => $numeroComprobante,
            'cae' => $cae,
            'caeVencimiento' => $caeVencimiento,
            'clienteNombre' => $clienteNombre,
            'clienteCuit' => $clienteCuit,
            'calculo' => $calculo,
            'referenciaAsociada' => $referenciaAsociada,
            'qr' => $qr,
        ])->render();

        // +14 por la línea nueva "Venta N°:", +140 si hay QR (imagen 110px + margen + separador).
        // Solo importa para el formato ticket — en A4 la página tiene alto fijo.
        $alturaExtra = 14 + ($qr ? 140 : 0);

        return $this->renderizar($html, $pedido->items->count(), $alturaExtra, $empresa->usaFormatoA4());
    }

    /**
     * `formato_comprobante` de la empresa (ticket/a4) decide qué vista
     * Blade usar — mismos datos, layout distinto. Ver Empresa::usaFormatoA4().
     */
    private function vista(Empresa $empresa, string $nombreBase): string
    {
        return 'facturacion.'.$nombreBase.($empresa->usaFormatoA4() ? '-a4' : '');
    }

    /**
     * Arma el QR estándar de AFIP/ARCA (RG 4892) que redirige a la consulta
     * pública del comprobante — mismo que trae cualquier factura electrónica
     * o ticket con CAE. Se genera 100% local con `endroid/qr-code` (backend
     * GD, sin llamadas de red ni servicios externos), así que no depende de
     * la conexión ni agrega latencia relevante a la generación del PDF.
     */
    private function qrAfip(
        Empresa $empresa,
        int $tipoComprobanteAfip,
        int $puntoVenta,
        string $numeroComprobante,
        string $cae,
        Carbon $fechaEmision,
        float $importe,
        ?string $clienteCuit,
    ): string {
        $payload = [
            'ver' => 1,
            'fecha' => $fechaEmision->format('Y-m-d'),
            'cuit' => (int) $empresa->cuit,
            'ptoVta' => $puntoVenta,
            'tipoCmp' => $tipoComprobanteAfip,
            'nroCmp' => $this->numeroComprobanteSinPuntoVenta($numeroComprobante),
            'importe' => round($importe, 2),
            'moneda' => 'PES',
            'ctz' => 1,
            'tipoDocRec' => $clienteCuit ? 80 : 99,
            'nroDocRec' => $clienteCuit ? (int) $clienteCuit : 0,
            'tipoCodAut' => 'E',
            'codAut' => (int) $cae,
        ];

        $url = 'https://www.afip.gob.ar/fe/qr/?p='.base64_encode(json_encode($payload));

        $qrCode = new QrCode(data: $url, size: 150, margin: 4);

        return (new PngWriter())->write($qrCode)->getDataUri();
    }

    /**
     * `numero_comprobante` llega en formato "PPPP-NNNNNNNN" (punto de venta +
     * número, ej. "00001-00000900") — un (int) directo sobre ese string se
     * corta en el guion y da el punto de venta (1), no el número real del
     * comprobante (900), que es lo que el campo `nroCmp` del QR de AFIP
     * necesita (el punto de venta ya va aparte en `ptoVta`).
     */
    private function numeroComprobanteSinPuntoVenta(string $numeroComprobante): int
    {
        $partes = explode('-', $numeroComprobante);

        return (int) end($partes);
    }

    private function renderizar(string $html, int $cantidadItems, int $alturaExtra = 0, bool $a4 = false): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);

        if ($a4) {
            // Hoja A4 de alto fijo — a diferencia del ticket, el contenido
            // fluye normal dentro de la página (y a una página siguiente si
            // llegara a no entrar), no hace falta calcular el alto.
            $dompdf->setPaper('A4', 'portrait');
        } else {
            // Alto variable: el ticket no tiene una cantidad fija de líneas
            // (depende de cuántos ítems tenga la venta). Una altura base cubre
            // encabezado + totales + footer (+20 por el pie "Desarrollado por"
            // que va en los tres tipos de ticket), y se suma una línea más por
            // cada ítem del detalle.
            $alto = 500 + $cantidadItems * 16 + $alturaExtra;
            $dompdf->setPaper([0, 0, 226.772, $alto], 'portrait');
        }

        $dompdf->render();

        return $dompdf->output();
    }

    public function nombreArchivo(Factura $factura, Pedido $pedido): string
    {
        return "ticket{$pedido->numero_venta}_{$this->sanitizarNombreArchivo($factura->cliente_nombre)}.pdf";
    }

    public function nombreArchivoNotaCredito(NotaCredito $notaCredito): string
    {
        $numero = $notaCredito->numero_comprobante ?? $notaCredito->id;

        return "nota-credito-{$notaCredito->factura->tipo_factura}-{$numero}.pdf";
    }

    public function nombreArchivoRemito(Pedido $pedido): string
    {
        $cliente = $pedido->cliente?->nombre ?? $pedido->cliente_nombre;

        return "ticket{$pedido->numero_venta}_{$this->sanitizarNombreArchivo($cliente)}.pdf";
    }

    public function nombreArchivoPresupuesto(Pedido $pedido): string
    {
        $cliente = $pedido->cliente?->nombre ?? $pedido->cliente_nombre;

        return "presupuesto{$pedido->numero_presupuesto}_{$this->sanitizarNombreArchivo($cliente)}.pdf";
    }

    /**
     * Saca del nombre los caracteres que Windows/la mayoría de sistemas de
     * archivos no aceptan en un nombre de archivo — se usa para el nombre
     * del cliente dentro del PDF del presupuesto, que llega tal cual lo
     * cargó el usuario (puede tener cualquier caracter).
     */
    private function sanitizarNombreArchivo(string $texto): string
    {
        $limpio = preg_replace('/[\\\\\/:*?"<>|]/', '', $texto) ?? $texto;
        $limpio = trim(preg_replace('/\s+/', ' ', $limpio) ?? $limpio);

        return $limpio !== '' ? $limpio : Pedido::CLIENTE_POR_DEFECTO;
    }
}
