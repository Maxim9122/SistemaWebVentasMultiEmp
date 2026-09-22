<?php

namespace App\Services;

use App\Models\Empresa;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Collection;

/**
 * PDFs "de reporte" (A4 apaisado, para imprimir un listado) — distintos del
 * ticket angosto de 80mm que arma ComprobantePdfService para una venta o
 * presupuesto puntual. Comparten el mismo pie "Desarrollado por"
 * (facturacion._pie-desarrollado-por) para que se vea igual en todos lados.
 */
class ReportePdfService
{
    /**
     * @param  Collection<int, \App\Models\Pedido>  $ventas
     */
    public function generarVentas(Collection $ventas, Empresa $empresa, string $descripcionFiltro): string
    {
        $html = view('facturacion.reporte-ventas-pdf', [
            'empresa' => $empresa,
            'ventas' => $ventas,
            'descripcionFiltro' => $descripcionFiltro,
            'totalGeneral' => $ventas->sum('total_cobrado'),
        ])->render();

        return $this->renderizar($html);
    }

    /**
     * @param  Collection<int, \App\Models\Producto>  $productos
     */
    public function generarProductos(Collection $productos, Empresa $empresa, bool $incluirStock, string $descripcionFiltro): string
    {
        $html = view('facturacion.reporte-productos-pdf', [
            'empresa' => $empresa,
            'productos' => $productos,
            'incluirStock' => $incluirStock,
            'descripcionFiltro' => $descripcionFiltro,
        ])->render();

        return $this->renderizar($html);
    }

    private function renderizar(string $html): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }
}
