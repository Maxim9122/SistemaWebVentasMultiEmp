<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Services\Facturacion\ComprobantePdfService;
use Illuminate\Http\Response;

/**
 * Sirve el PDF del ticket (presupuesto, factura o remito) sin login — pensado
 * para el link que se manda por WhatsApp al teléfono del cliente, que nunca
 * tiene una cuenta en el sistema. La seguridad no depende de sesión ni de
 * `empresa_id` del usuario (no hay ninguno acá): depende 100% de que la URL
 * venga firmada por Laravel (middleware `signed`, ver routes/web.php) — sin
 * la firma válida, ni siquiera llega a este controller.
 */
class TicketPublicoController extends Controller
{
    public function __invoke(Pedido $pedido, string $tipo, ComprobantePdfService $pdf): Response
    {
        $pedido->load(['items', 'vendedor', 'cajero', 'empresa', 'cliente', 'factura']);

        [$contenido, $nombreArchivo] = match ($tipo) {
            'presupuesto' => $this->presupuesto($pedido, $pdf),
            'factura' => $this->factura($pedido, $pdf),
            'remito' => $this->remito($pedido, $pdf),
            default => abort(404),
        };

        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$nombreArchivo}\"",
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function presupuesto(Pedido $pedido, ComprobantePdfService $pdf): array
    {
        if (! $pedido->esPresupuesto()) {
            abort(404);
        }

        return [$pdf->generarPresupuesto($pedido), $pdf->nombreArchivoPresupuesto($pedido)];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function factura(Pedido $pedido, ComprobantePdfService $pdf): array
    {
        if (! $pedido->esCobrado() || ! $pedido->factura?->cae) {
            abort(404);
        }

        return [$pdf->generarFactura($pedido->factura, $pedido), $pdf->nombreArchivo($pedido->factura, $pedido)];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function remito(Pedido $pedido, ComprobantePdfService $pdf): array
    {
        if (! $pedido->esCobrado() || $pedido->tipo_comprobante !== 'remito') {
            abort(404);
        }

        return [$pdf->generarRemito($pedido), $pdf->nombreArchivoRemito($pedido)];
    }
}
