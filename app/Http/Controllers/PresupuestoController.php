<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgregarItemCarritoRequest;
use App\Http\Requests\RegistrarCobroRequest;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Factura;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\User;
use App\Services\EnvioTicketService;
use App\Services\Facturacion\ComprobantePdfService;
use App\Services\Facturacion\EmisionComprobanteService;
use App\Services\ProcesadorDeCobro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * Un presupuesto es un Pedido más (mismo `items`/`total`, misma tabla) en
 * `estado=presupuesto` — nunca toca stock (eso solo pasa dentro de
 * ProcesadorDeCobro::cobrar(), y acá no se lo llama hasta que se aprieta
 * "Cobrar"). Se numera aparte de las ventas (`numero_presupuesto`, contador
 * propio en `empresas.ultimo_numero_presupuesto`) porque tiene que ser
 * buscable por número desde el momento en que se crea, mucho antes de que
 * exista un `numero_venta` (que recién se asigna al cobrar).
 *
 * A diferencia de Carritos (personal, por vendedor, roles vendedor/
 * cajero_vendedor), esto es una lista compartida de toda la empresa — mismo
 * criterio de autorización por `empresa_id` que Caja/Pedidos/Ventas/Créditos,
 * visible para admin/cajero/cajero_vendedor (admin no vende, pero arma y
 * gestiona presupuestos igual; para cobrarlo de verdad hace falta caja
 * abierta, mismo requisito que ya exige ProcesadorDeCobro::cobrar() para
 * cualquier cobro).
 */
class PresupuestoController extends Controller
{
    public function index(Request $request): View
    {
        $porPagina = (int) $request->input('por_pagina', 10);

        if (! in_array($porPagina, [10, 50, 100], true)) {
            $porPagina = 10;
        }

        $numero = trim((string) $request->input('numero', ''));
        $fechaDesde = $request->input('fecha_desde', now()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));
        $fechaFiltradaManualmente = $request->has('fecha_desde') || $request->has('fecha_hasta');

        $presupuestos = Pedido::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->where('estado', Pedido::ESTADO_PRESUPUESTO)
            ->when($numero !== '', fn ($q) => $q->where('numero_presupuesto', (int) $numero))
            ->when($fechaDesde, fn ($q) => $q->whereDate('created_at', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($q) => $q->whereDate('created_at', '<=', $fechaHasta))
            ->with(['vendedor', 'cliente'])
            ->orderByDesc('created_at')
            ->paginate($porPagina)
            ->withQueryString();

        return view('presupuestos.index', [
            'presupuestos' => $presupuestos,
            'porPagina' => $porPagina,
            'numero' => $numero,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'fechaFiltradaManualmente' => $fechaFiltradaManualmente,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('presupuestos.show', $this->crearPresupuesto($request->user()));
    }

    public function show(Request $request, Pedido $pedido): View
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEsPresupuesto($pedido);

        return view('presupuestos.show', [
            'pedido' => $pedido->load(['items.producto', 'cliente']),
            'productos' => Producto::where('empresa_id', $request->user()->empresa_id)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(),
            'puedeCambiarPrecio' => $request->user()->puedeCambiarPrecioVenta(),
            'empresa' => $request->user()->empresa,
            'clientes' => Cliente::where('empresa_id', $request->user()->empresa_id)->where('activo', true)->orderBy('nombre')->get(),
            'ticketUrlFirmada' => URL::temporarySignedRoute(
                'ticket.publico',
                now()->addDays(30),
                ['pedido' => $pedido->id, 'tipo' => 'presupuesto'],
            ),
        ]);
    }

    public function actualizarCliente(Request $request, Pedido $pedido): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEsPresupuesto($pedido);

        $request->validate([
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'cliente_nombre' => ['nullable', 'string', 'max:255'],
            'cliente_cuit' => ['nullable', 'string', 'max:20'],
            'cliente_telefono' => ['nullable', 'string', 'max:50'],
            'cliente_email' => ['nullable', 'email', 'max:255'],
        ]);

        $clienteId = $request->filled('cliente_id') ? (int) $request->input('cliente_id') : null;

        if ($clienteId) {
            $cliente = Cliente::where('id', $clienteId)->where('empresa_id', $pedido->empresa_id)->first();

            if (! $cliente) {
                abort(404);
            }
        } elseif ($request->filled('cliente_nombre') && $request->filled('cliente_cuit')) {
            $clienteId = Cliente::firstOrCreate(
                ['empresa_id' => $pedido->empresa_id, 'cuit' => $request->input('cliente_cuit')],
                [
                    'nombre' => $request->input('cliente_nombre'),
                    'telefono' => $request->input('cliente_telefono'),
                    'email' => $request->input('cliente_email'),
                ],
            )->id;
        }

        $pedido->update([
            'cliente_id' => $clienteId,
            'cliente_nombre' => $clienteId ? Cliente::find($clienteId)->nombre : Pedido::CLIENTE_POR_DEFECTO,
        ]);

        return redirect()->route('presupuestos.show', $pedido)->with('status', 'Cliente actualizado.');
    }

    public function agregarItem(AgregarItemCarritoRequest $request, Pedido $pedido): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEsPresupuesto($pedido);

        $producto = Producto::findOrFail($request->validated('producto_id'));
        $cantidad = (int) $request->validated('cantidad');
        $itemExistente = $pedido->items()->where('producto_id', $producto->id)->first();

        if ($itemExistente) {
            $nuevaCantidad = $itemExistente->cantidad + $cantidad;
            $resolucion = $this->resolverPrecio(
                $request,
                $producto,
                $nuevaCantidad,
                (bool) $itemExistente->precio_manual,
                (float) $itemExistente->precio_unitario,
                conservarManualSiNoSeEnviaPrecio: true,
            );

            $itemExistente->update([
                'cantidad' => $nuevaCantidad,
                'precio_unitario' => $resolucion['precio'],
                'precio_manual' => $resolucion['manual'],
                'subtotal' => round($nuevaCantidad * $resolucion['precio'], 2),
            ]);
        } else {
            $resolucion = $this->resolverPrecio($request, $producto, $cantidad, false, (float) $producto->precio, conservarManualSiNoSeEnviaPrecio: false);

            PedidoItem::create([
                'pedido_id' => $pedido->id,
                'producto_id' => $producto->id,
                'nombre_producto' => $producto->nombre,
                'cantidad' => $cantidad,
                'precio_original' => $producto->precio,
                'precio_unitario' => $resolucion['precio'],
                'precio_manual' => $resolucion['manual'],
                'subtotal' => round($cantidad * $resolucion['precio'], 2),
            ]);
        }

        $pedido->recalcularTotal();

        return redirect()->route('presupuestos.show', $pedido)->with('status', 'Producto agregado.');
    }

    public function actualizarItem(AgregarItemCarritoRequest $request, Pedido $pedido, PedidoItem $item): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEsPresupuesto($pedido);
        $this->autorizarItem($pedido, $item);

        $cantidad = (int) $request->validated('cantidad');
        $resolucion = $this->resolverPrecio(
            $request,
            $item->producto ?? new Producto(['precio' => $item->precio_original]),
            $cantidad,
            (bool) $item->precio_manual,
            (float) $item->precio_unitario,
            conservarManualSiNoSeEnviaPrecio: false,
        );

        $item->update([
            'cantidad' => $cantidad,
            'precio_unitario' => $resolucion['precio'],
            'precio_manual' => $resolucion['manual'],
            'subtotal' => round($cantidad * $resolucion['precio'], 2),
        ]);

        $pedido->recalcularTotal();

        return redirect()->route('presupuestos.show', $pedido)->with('status', 'Línea actualizada.');
    }

    public function quitarItem(Request $request, Pedido $pedido, PedidoItem $item): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEsPresupuesto($pedido);
        $this->autorizarItem($pedido, $item);

        $item->delete();
        $pedido->recalcularTotal();

        return redirect()->route('presupuestos.show', $pedido)->with('status', 'Producto quitado.');
    }

    /**
     * Se comporta como una venta normal: mismo ProcesadorDeCobro::cobrar()
     * que usa Caja, con la misma validación de montos/factura/fiado
     * (RegistrarCobroRequest ya es genérico sobre el nombre del route param
     * {pedido}, no hace falta una Request nueva). Recién ACÁ se descuenta
     * stock por primera vez en toda la vida de este presupuesto. Al cobrarlo
     * deja de ser un presupuesto (el estado pasa a cobrado dentro de
     * cobrar()) y termina viéndose en Ventas — y en Créditos también, si
     * quedó algo fiado — sin lógica extra: son las mismas listas que ya
     * filtran por estado/monto_fiado.
     */
    public function cobrar(
        RegistrarCobroRequest $request,
        Pedido $pedido,
        ProcesadorDeCobro $procesador,
        EmisionComprobanteService $emisor,
    ): RedirectResponse {
        $this->autorizar($request, $pedido);
        $this->asegurarEsPresupuesto($pedido);

        try {
            $factura = $procesador->cobrar(
                $pedido,
                $request->user(),
                [
                    'efectivo' => $request->validated('monto_efectivo'),
                    'tarjeta' => $request->validated('monto_tarjeta'),
                    'transferencia' => $request->validated('monto_transferencia'),
                ],
                $request->validated('tipo_comprobante'),
                $request->filled('cliente_id') ? (int) $request->validated('cliente_id') : $pedido->cliente_id,
                $request->filled('cliente_nombre') && $request->filled('cliente_cuit') ? [
                    'nombre' => $request->validated('cliente_nombre'),
                    'cuit' => $request->validated('cliente_cuit'),
                    'telefono' => $request->validated('cliente_telefono'),
                ] : null,
                $request->validated('tipo_factura'),
                (float) ($request->validated('monto_fiado') ?: 0),
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['stock' => $e->getMessage()]);
        }

        if ($factura) {
            $pedido->refresh();
            $emisor->emitir($factura, $pedido);
        }

        if ($request->validated('tipo_comprobante') === 'remito' || $factura?->estado === Factura::ESTADO_APROBADA) {
            session()->flash('pedido_comprobante_listo_id', $pedido->id);
        }

        return redirect()->route('ventas.show', $pedido)->with('status', 'Presupuesto cobrado — ya está en Ventas.');
    }

    public function cancelar(Request $request, Pedido $pedido): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEsPresupuesto($pedido);

        $pedido->update(['estado' => Pedido::ESTADO_CANCELADO]);

        return redirect()->route('presupuestos.index')->with('status', 'Presupuesto cancelado.');
    }

    public function ticketPdf(Request $request, Pedido $pedido, ComprobantePdfService $pdf): Response
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEsPresupuesto($pedido);

        $pedido->load(['items', 'vendedor', 'cliente']);

        return response($pdf->generarPresupuesto($pedido), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->nombreArchivoPresupuesto($pedido).'"',
        ]);
    }

    public function imprimirTicket(Request $request, Pedido $pedido, ComprobantePdfService $pdf): Response
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEsPresupuesto($pedido);

        $pedido->load(['items', 'vendedor', 'cliente']);

        return response($pdf->generarPresupuestoHtml($pedido, modoWeb: true));
    }

    public function enviarEmail(Request $request, Pedido $pedido, ComprobantePdfService $pdf, EnvioTicketService $envio): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEsPresupuesto($pedido);

        $request->validate(['email' => ['required', 'email', 'max:255']]);

        $pedido->load(['items', 'vendedor', 'cliente']);

        try {
            $envio->enviarPorEmail(
                empresa: $pedido->empresa,
                emailDestino: $request->input('email'),
                tituloDocumento: 'Presupuesto',
                numero: (string) $pedido->numero_presupuesto,
                pdfContenido: $pdf->generarPresupuesto($pedido),
                pdfNombreArchivo: $pdf->nombreArchivoPresupuesto($pedido),
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['email' => 'No se pudo enviar el email. Probá de nuevo en unos minutos.']);
        }

        return redirect()->route('presupuestos.show', $pedido)->with('status', 'Presupuesto enviado por email a '.$request->input('email').'.');
    }

    /**
     * Mismo bloqueo por lock+incremento que ya usa ProcesadorDeCobro para
     * `numero_venta` — evita que dos presupuestos creados en simultáneo por
     * distintos usuarios de la misma empresa se lleven el mismo número.
     */
    private function crearPresupuesto(User $usuario): Pedido
    {
        return DB::transaction(function () use ($usuario) {
            $empresa = Empresa::where('id', $usuario->empresa_id)->lockForUpdate()->first();
            $numero = $empresa->ultimo_numero_presupuesto + 1;
            $empresa->update(['ultimo_numero_presupuesto' => $numero]);

            return Pedido::create([
                'empresa_id' => $usuario->empresa_id,
                'vendedor_id' => $usuario->id,
                'cliente_nombre' => Pedido::CLIENTE_POR_DEFECTO,
                'estado' => Pedido::ESTADO_PRESUPUESTO,
                'numero_presupuesto' => $numero,
            ]);
        });
    }

    /**
     * Igual a CarritoController::resolverPrecio() — se duplica a propósito
     * (en vez de extraer un helper compartido) para no tocar el código ya
     * probado de Carritos por una feature nueva; la lógica es chica y estable.
     *
     * @return array{precio: float, manual: bool}
     */
    private function resolverPrecio(
        AgregarItemCarritoRequest $request,
        Producto $producto,
        int $cantidad,
        bool $eraManual,
        float $precioManualPrevio,
        bool $conservarManualSiNoSeEnviaPrecio,
    ): array {
        $precioSolicitado = $request->validated('precio_unitario');
        $puedeCambiar = $request->user()->puedeCambiarPrecioVenta();

        if ($puedeCambiar && filled($precioSolicitado)) {
            return ['precio' => (float) $precioSolicitado, 'manual' => true];
        }

        if ($puedeCambiar && $conservarManualSiNoSeEnviaPrecio && $eraManual) {
            return ['precio' => $precioManualPrevio, 'manual' => true];
        }

        return ['precio' => $producto->precioParaCantidad($cantidad)['precio'], 'manual' => false];
    }

    private function autorizar(Request $request, Pedido $pedido): void
    {
        if ($pedido->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }
    }

    private function autorizarItem(Pedido $pedido, PedidoItem $item): void
    {
        if ($item->pedido_id !== $pedido->id) {
            abort(404);
        }
    }

    private function asegurarEsPresupuesto(Pedido $pedido): void
    {
        if (! $pedido->esPresupuesto()) {
            abort(404);
        }
    }
}
