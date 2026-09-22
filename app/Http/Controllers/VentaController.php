<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmarReaperturaCobroVentaRequest;
use App\Http\Requests\EditarItemsVentaRequest;
use App\Models\Pedido;
use App\Models\Producto;
use App\Services\EnvioTicketService;
use App\Services\Facturacion\ComprobantePdfService;
use App\Services\Facturacion\EmisionComprobanteService;
use App\Services\Facturacion\EmisionNotaCreditoService;
use App\Services\ProcesadorDeCobro;
use App\Services\ReportePdfService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class VentaController extends Controller
{
    public function index(Request $request): View
    {
        $porPagina = (int) $request->input('por_pagina', 10);

        if (! in_array($porPagina, [10, 50, 100], true)) {
            $porPagina = 10;
        }

        [$numero, $fechaDesde, $fechaHasta, $fechaFiltradaManualmente] = $this->filtrosDesde($request);

        $ventas = $this->ventasFiltradas($request, $numero, $fechaDesde, $fechaHasta)
            ->with(['vendedor', 'cajero', 'factura'])
            ->orderByDesc('cobrado_at')
            ->paginate($porPagina)
            ->withQueryString();

        return view('ventas.index', [
            'ventas' => $ventas,
            'porPagina' => $porPagina,
            'numero' => $numero,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'fechaFiltradaManualmente' => $fechaFiltradaManualmente,
        ]);
    }

    /**
     * PDF apaisado, pensado para imprimir, con el mismo listado que ya se ve
     * filtrado en pantalla (sin paginar — todo lo que matchea el filtro).
     */
    public function exportarPdf(Request $request, ReportePdfService $reportes): Response
    {
        [$numero, $fechaDesde, $fechaHasta] = $this->filtrosDesde($request);

        $ventas = $this->ventasFiltradas($request, $numero, $fechaDesde, $fechaHasta)
            ->with(['vendedor', 'cajero', 'factura'])
            ->orderBy('cobrado_at')
            ->get();

        $descripcionFiltro = $this->descripcionFiltroVentas($numero, $fechaDesde, $fechaHasta);

        $pdf = $reportes->generarVentas($ventas, $request->user()->empresa, $descripcionFiltro);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="ventas.pdf"',
        ]);
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: bool}
     */
    private function filtrosDesde(Request $request): array
    {
        $numero = trim((string) $request->input('numero', ''));
        $fechaDesde = $request->input('fecha_desde', now()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));
        $fechaFiltradaManualmente = $request->has('fecha_desde') || $request->has('fecha_hasta');

        return [$numero, $fechaDesde, $fechaHasta, $fechaFiltradaManualmente];
    }

    private function ventasFiltradas(Request $request, string $numero, ?string $fechaDesde, ?string $fechaHasta): Builder
    {
        return Pedido::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->where('estado', Pedido::ESTADO_COBRADO)
            ->when($numero !== '', fn ($q) => $q->where('numero_venta', (int) $numero))
            ->when($fechaDesde, fn ($q) => $q->whereDate('cobrado_at', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($q) => $q->whereDate('cobrado_at', '<=', $fechaHasta));
    }

    private function descripcionFiltroVentas(string $numero, ?string $fechaDesde, ?string $fechaHasta): string
    {
        $partes = [];

        if ($fechaDesde || $fechaHasta) {
            $partes[] = 'Del '.($fechaDesde ? Carbon::parse($fechaDesde)->format('d/m/Y') : '—')
                .' al '.($fechaHasta ? Carbon::parse($fechaHasta)->format('d/m/Y') : '—');
        }

        if ($numero !== '') {
            $partes[] = 'N° de venta: '.$numero;
        }

        return $partes === [] ? 'Todas las ventas' : implode(' — ', $partes);
    }

    public function show(Request $request, Pedido $pedido): View
    {
        $this->autorizar($request, $pedido);
        $this->asegurarCobrado($pedido);

        $tieneComprobanteListo = ($pedido->tipo_comprobante === 'factura' && $pedido->factura?->cae)
            || $pedido->tipo_comprobante === 'remito';

        return view('ventas.show', [
            'pedido' => $pedido->load([
                'items', 'vendedor', 'cajero', 'cliente', 'factura.cliente', 'factura.notaCredito', 'modificaciones.user',
                'facturas' => fn ($q) => $q->orderByDesc('id'),
                'facturas.notaCredito',
            ]),
            'puedeEditar' => $this->puedeEditar($request),
            'puedeAnular' => $this->puedeAnularFactura($request),
            'ticketUrlFirmada' => $tieneComprobanteListo
                ? URL::temporarySignedRoute(
                    'ticket.publico',
                    now()->addDays(30),
                    ['pedido' => $pedido->id, 'tipo' => $pedido->tipo_comprobante === 'factura' ? 'factura' : 'remito'],
                )
                : null,
        ]);
    }

    public function edit(Request $request, Pedido $pedido): View
    {
        $this->autorizar($request, $pedido);
        $this->asegurarCobrado($pedido);
        $this->asegurarPuedeEditar($request);

        return view('ventas.editar', [
            'pedido' => $pedido->load(['items', 'factura.notaCredito']),
            'productos' => Producto::where('empresa_id', $request->user()->empresa_id)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(),
            'puedeCambiarPrecio' => $request->user()->puedeCambiarPrecioVenta(),
        ]);
    }

    /**
     * Arma la lista de ítems propuestos desde el request, incluyendo el
     * precio manual solo si el usuario tiene permiso para cambiar precios
     * (`permite_cambiar_precio_venta` de la empresa + `puede_cambiar_precio_venta`
     * del usuario) — así, aunque alguien mande `precio_unitario` a mano en el
     * POST sin tener el permiso, se ignora acá y nunca llega a resolverItems().
     *
     * @return list<array{producto_id: int, cantidad: int, precio_unitario: float|null}>
     */
    private function itemsPropuestosDesde(Request $request): array
    {
        $puedeCambiarPrecio = $request->user()->puedeCambiarPrecioVenta();

        return collect($request->validated('items'))
            ->map(fn ($i) => [
                'producto_id' => (int) $i['producto_id'],
                'cantidad' => (int) $i['cantidad'],
                'precio_unitario' => $puedeCambiarPrecio && filled($i['precio_unitario'] ?? null)
                    ? (float) $i['precio_unitario']
                    : null,
            ])
            ->all();
    }

    public function update(
        EditarItemsVentaRequest $request,
        Pedido $pedido,
        ProcesadorDeCobro $procesador,
        EmisionNotaCreditoService $emisorNC,
        EmisionComprobanteService $emisorFactura,
    ): View|RedirectResponse {
        $this->autorizar($request, $pedido);
        $this->asegurarCobrado($pedido);
        $this->asegurarPuedeEditar($request);

        $itemsPropuestos = $this->itemsPropuestosDesde($request);

        try {
            $resuelto = $procesador->resolverItems($itemsPropuestos, $pedido->items()->get());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        $totalNuevo = $resuelto['total'];
        $motivo = $request->validated('motivo');
        $facturaAprobada = $pedido->factura?->estaAprobada() ?? false;

        if (bccomp((string) $totalNuevo, (string) $pedido->total_cobrado, 2) === 0) {
            $status = 'Venta actualizada.';

            try {
                if ($facturaAprobada) {
                    $resultado = $procesador->editarVentaFacturada($pedido, $request->user(), $itemsPropuestos, $motivo);
                    $pedido->refresh();

                    if ($resultado['notaCredito']) {
                        $emisorNC->emitir($resultado['notaCredito']);
                        $status = 'Venta actualizada — se emitió la nota de crédito de la factura anterior y una factura nueva.';
                    } else {
                        $status = 'Venta actualizada — se generó una factura nueva (la anterior ya tenía nota de crédito).';
                    }

                    $emisorFactura->emitir($resultado['facturaNueva'], $pedido);
                } else {
                    $procesador->editarItems($pedido, $request->user(), $itemsPropuestos, $motivo);
                }
            } catch (\RuntimeException $e) {
                return back()->withErrors(['stock' => $e->getMessage()])->withInput();
            }

            return redirect()->route('ventas.show', $pedido)->with('status', $status);
        }

        return view('ventas.reabrir-cobro', [
            'pedido' => $pedido,
            'pedidoProspectivo' => new Pedido(['total' => $totalNuevo]),
            'empresa' => $request->user()->empresa,
            'itemsResueltos' => $resuelto['items'],
            'itemsPropuestos' => $itemsPropuestos,
            'motivo' => $motivo,
        ]);
    }

    public function confirmarReapertura(
        ConfirmarReaperturaCobroVentaRequest $request,
        Pedido $pedido,
        ProcesadorDeCobro $procesador,
        EmisionNotaCreditoService $emisorNC,
        EmisionComprobanteService $emisorFactura,
    ): View|RedirectResponse {
        $this->autorizar($request, $pedido);
        $this->asegurarCobrado($pedido);
        $this->asegurarPuedeEditar($request);

        $itemsPropuestos = $this->itemsPropuestosDesde($request);

        $motivo = $request->validated('motivo');

        $montosNuevos = [
            'efectivo' => $request->validated('monto_efectivo'),
            'tarjeta' => $request->validated('monto_tarjeta'),
            'transferencia' => $request->validated('monto_transferencia'),
        ];

        $facturaAprobada = $pedido->factura?->estaAprobada() ?? false;
        $status = 'Venta actualizada y cobro reabierto.';

        try {
            if ($facturaAprobada) {
                $resultado = $procesador->editarVentaFacturada($pedido, $request->user(), $itemsPropuestos, $motivo, $montosNuevos);
                $pedido->refresh();

                if ($resultado['notaCredito']) {
                    $emisorNC->emitir($resultado['notaCredito']);
                    $status = 'Venta actualizada, cobro reabierto — se emitió la nota de crédito de la factura anterior y una factura nueva.';
                } else {
                    $status = 'Venta actualizada, cobro reabierto — se generó una factura nueva (la anterior ya tenía nota de crédito).';
                }

                $emisorFactura->emitir($resultado['facturaNueva'], $pedido);
            } else {
                $procesador->editarItems($pedido, $request->user(), $itemsPropuestos, $motivo, $montosNuevos);
            }
        } catch (\RuntimeException $e) {
            $resuelto = $procesador->resolverItems($itemsPropuestos, $pedido->items()->get());

            return view('ventas.reabrir-cobro', [
                'pedido' => $pedido,
                'pedidoProspectivo' => new Pedido(['total' => $resuelto['total']]),
                'empresa' => $request->user()->empresa,
                'itemsResueltos' => $resuelto['items'],
                'itemsPropuestos' => $itemsPropuestos,
                'motivo' => $motivo,
            ])->withErrors(['stock' => $e->getMessage()]);
        }

        return redirect()->route('ventas.show', $pedido)->with('status', $status);
    }

    public function comprobantePdf(Request $request, Pedido $pedido, ComprobantePdfService $pdf): Response
    {
        $this->autorizar($request, $pedido);
        $this->asegurarCobrado($pedido);

        $factura = $pedido->factura;

        if (! $factura || ! $factura->cae) {
            abort(404);
        }

        $pedido->load(['items', 'vendedor', 'cajero']);

        return response($pdf->generarFactura($factura, $pedido), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->nombreArchivo($factura, $pedido).'"',
        ]);
    }

    public function notaCreditoPdf(Request $request, Pedido $pedido, ComprobantePdfService $pdf): Response
    {
        $this->autorizar($request, $pedido);
        $this->asegurarCobrado($pedido);

        $notaCredito = $pedido->factura?->notaCredito;

        if (! $notaCredito || ! $notaCredito->cae) {
            abort(404);
        }

        $pedido->load(['items', 'vendedor', 'cajero']);

        return response($pdf->generarNotaCredito($notaCredito, $pedido), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->nombreArchivoNotaCredito($notaCredito).'"',
        ]);
    }

    public function remitoPdf(Request $request, Pedido $pedido, ComprobantePdfService $pdf): Response
    {
        $this->autorizar($request, $pedido);
        $this->asegurarCobrado($pedido);

        if ($pedido->tipo_comprobante !== 'remito') {
            abort(404);
        }

        $pedido->load(['items', 'vendedor', 'cajero']);

        return response($pdf->generarRemito($pedido), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->nombreArchivoRemito($pedido).'"',
        ]);
    }

    public function enviarEmail(Request $request, Pedido $pedido, ComprobantePdfService $pdf, EnvioTicketService $envio): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarCobrado($pedido);

        $request->validate(['email' => ['required', 'email', 'max:255']]);

        $pedido->load(['items', 'vendedor', 'cajero']);

        $factura = $pedido->factura;
        $esFactura = $pedido->tipo_comprobante === 'factura' && $factura?->cae;

        if (! $esFactura && $pedido->tipo_comprobante !== 'remito') {
            return back()->withErrors(['email' => 'Todavía no hay un comprobante listo para enviar.']);
        }

        try {
            if ($esFactura) {
                $envio->enviarPorEmail(
                    empresa: $pedido->empresa,
                    emailDestino: $request->input('email'),
                    tituloDocumento: 'Factura '.$factura->tipo_factura,
                    numero: $factura->numero_comprobante ?? (string) $factura->id,
                    pdfContenido: $pdf->generarFactura($factura, $pedido),
                    pdfNombreArchivo: $pdf->nombreArchivo($factura, $pedido),
                );
            } else {
                $envio->enviarPorEmail(
                    empresa: $pedido->empresa,
                    emailDestino: $request->input('email'),
                    tituloDocumento: 'Remito',
                    numero: (string) $pedido->numero_venta,
                    pdfContenido: $pdf->generarRemito($pedido),
                    pdfNombreArchivo: $pdf->nombreArchivoRemito($pedido),
                );
            }
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['email' => 'No se pudo enviar el email. Probá de nuevo en unos minutos.']);
        }

        return redirect()->route('ventas.show', $pedido)->with('status', 'Comprobante enviado por email a '.$request->input('email').'.');
    }

    public function reintentarFacturacion(Request $request, Pedido $pedido, EmisionComprobanteService $emisor): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarCobrado($pedido);

        $factura = $pedido->factura;

        if (! $factura) {
            abort(404);
        }

        $emisor->emitir($factura, $pedido);

        return redirect()->route('ventas.show', $pedido)->with('status', 'Se reintentó la facturación electrónica.');
    }

    public function anularFactura(Request $request, Pedido $pedido, ProcesadorDeCobro $procesador, EmisionNotaCreditoService $emisor): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarCobrado($pedido);
        $this->asegurarPuedeAnular($request);

        $motivo = $request->validate(['motivo' => ['required', 'string', 'max:1000']])['motivo'];

        try {
            $notaCredito = $procesador->anularVenta($pedido, $request->user(), $motivo);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['anular' => $e->getMessage()]);
        }

        $emisor->emitir($notaCredito);

        return redirect()->route('ventas.show', $pedido)->with('status', 'Factura anulada — se emitió la nota de crédito.');
    }

    public function reintentarNotaCredito(Request $request, Pedido $pedido, EmisionNotaCreditoService $emisor): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarCobrado($pedido);
        $this->asegurarPuedeAnular($request);

        $notaCredito = $pedido->factura?->notaCredito;

        if (! $notaCredito) {
            abort(404);
        }

        $emisor->emitir($notaCredito);

        return redirect()->route('ventas.show', $pedido)->with('status', 'Se reintentó la emisión de la nota de crédito.');
    }

    private function autorizar(Request $request, Pedido $pedido): void
    {
        if ($pedido->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }
    }

    private function asegurarCobrado(Pedido $pedido): void
    {
        if (! $pedido->esCobrado()) {
            abort(404);
        }
    }

    private function puedeEditar(Request $request): bool
    {
        $usuario = $request->user();

        return $usuario->role === 'admin'
            || (in_array($usuario->role, ['cajero', 'cajero_vendedor'], true) && $usuario->empresa->permite_cajero_modificar_ventas);
    }

    private function asegurarPuedeEditar(Request $request): void
    {
        if (! $this->puedeEditar($request)) {
            abort(403);
        }
    }

    private function puedeAnularFactura(Request $request): bool
    {
        $usuario = $request->user();

        return $usuario->role === 'admin'
            || (in_array($usuario->role, ['cajero', 'cajero_vendedor'], true) && $usuario->empresa->permite_cajero_notas_credito);
    }

    private function asegurarPuedeAnular(Request $request): void
    {
        if (! $this->puedeAnularFactura($request)) {
            abort(403);
        }
    }
}
