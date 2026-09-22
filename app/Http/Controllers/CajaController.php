<?php

namespace App\Http\Controllers;

use App\Http\Requests\EditarItemsVentaRequest;
use App\Http\Requests\RegistrarCobroRequest;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Pedido;
use App\Models\Producto;
use App\Services\Facturacion\EmisionComprobanteService;
use App\Services\ProcesadorDeCobro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CajaController extends Controller
{
    public function index(Request $request): View
    {
        $pedidos = Pedido::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->where('estado', Pedido::ESTADO_EN_CAJA)
            ->with('vendedor')
            ->oldest()
            ->get();

        return view('caja.index', ['pedidos' => $pedidos]);
    }

    public function show(Request $request, Pedido $pedido): View
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEnCaja($pedido);

        return view('caja.show', [
            'pedido' => $pedido->load(['items', 'vendedor']),
            'empresa' => $request->user()->empresa,
            'clientes' => Cliente::where('empresa_id', $request->user()->empresa_id)->where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function cobrar(
        RegistrarCobroRequest $request,
        Pedido $pedido,
        ProcesadorDeCobro $procesador,
        EmisionComprobanteService $emisor,
    ): RedirectResponse {
        $this->autorizar($request, $pedido);
        $this->asegurarEnCaja($pedido);

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
                $request->filled('cliente_id') ? (int) $request->validated('cliente_id') : null,
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

        return redirect()->route('caja.index')->with('status', 'Pedido cobrado.');
    }

    public function editar(Request $request, Pedido $pedido): View
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEnCaja($pedido);

        return view('pedidos.editar-items', [
            'titulo' => 'Editar pedido en caja',
            'pedido' => $pedido->load('items'),
            'productos' => Producto::where('empresa_id', $request->user()->empresa_id)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(),
            'actionUrl' => route('caja.actualizarItems', $pedido),
            'volverUrl' => route('caja.show', $pedido),
            'puedeCambiarPrecio' => $request->user()->puedeCambiarPrecioVenta(),
        ]);
    }

    public function actualizarItems(EditarItemsVentaRequest $request, Pedido $pedido, ProcesadorDeCobro $procesador): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEnCaja($pedido);

        $puedeCambiarPrecio = $request->user()->puedeCambiarPrecioVenta();

        $itemsPropuestos = collect($request->validated('items'))
            ->map(fn ($i) => [
                'producto_id' => (int) $i['producto_id'],
                'cantidad' => (int) $i['cantidad'],
                'precio_unitario' => $puedeCambiarPrecio && filled($i['precio_unitario'] ?? null)
                    ? (float) $i['precio_unitario']
                    : null,
            ])
            ->all();

        try {
            $procesador->editarItemsPreCobro($pedido, $itemsPropuestos);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('caja.show', $pedido)->with('status', 'Pedido actualizado.');
    }

    private function autorizar(Request $request, Pedido $pedido): void
    {
        if ($pedido->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }
    }

    private function asegurarEnCaja(Pedido $pedido): void
    {
        if (! $pedido->esEnCaja()) {
            abort(404);
        }
    }
}
