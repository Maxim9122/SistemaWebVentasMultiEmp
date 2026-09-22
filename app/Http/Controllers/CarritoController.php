<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgregarItemCarritoRequest;
use App\Http\Requests\CerrarCarritoRequest;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\User;
use App\Services\Facturacion\EmisionComprobanteService;
use App\Services\ProcesadorDeCobro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarritoController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $pedido = $this->carritoAbierto($request->user()) ?? $this->crearCarrito($request->user());

        // Esta acción siempre redirige de nuevo a carritos.show, lo que consume
        // "el próximo request" del flash puesto en cerrar() sin llegar a mostrarlo
        // — keep() lo extiende un request más para que sobreviva a este doble redirect.
        session()->keep(['status', 'pedido_comprobante_listo_id']);

        return redirect()->route('carritos.show', $pedido);
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        if (! $usuario->empresa->permite_multiples_carritos) {
            $existente = $this->carritoAbierto($usuario);

            if ($existente) {
                return redirect()->route('carritos.show', $existente)
                    ->with('status', 'Ya tenías un carrito abierto, seguiste con ese.');
            }
        }

        return redirect()->route('carritos.show', $this->crearCarrito($usuario));
    }

    public function show(Request $request, Pedido $pedido): View
    {
        $this->autorizar($request, $pedido);

        $otrosCarritos = Pedido::query()
            ->where('vendedor_id', $request->user()->id)
            ->where('estado', Pedido::ESTADO_CARRITO)
            ->where('id', '!=', $pedido->id)
            ->get();

        $productos = Producto::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('carritos.show', [
            'pedido' => $pedido->load('items.producto'),
            'otrosCarritos' => $otrosCarritos,
            'productos' => $productos,
            'puedeCambiarPrecio' => $request->user()->puedeCambiarPrecioVenta(),
            'empresa' => $request->user()->empresa,
            'clientes' => Cliente::where('empresa_id', $request->user()->empresa_id)->where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function renombrar(Request $request, Pedido $pedido): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEsCarrito($pedido);

        $request->validate(['cliente_nombre' => ['nullable', 'string', 'max:255']]);

        $nombre = trim((string) $request->input('cliente_nombre'));

        $pedido->update(['cliente_nombre' => $nombre !== '' ? $nombre : Pedido::CLIENTE_POR_DEFECTO]);

        return redirect()->route('carritos.show', $pedido);
    }

    public function agregarItem(AgregarItemCarritoRequest $request, Pedido $pedido): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEsCarrito($pedido);

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

        return redirect()->route('carritos.show', $pedido)->with('status', 'Producto agregado.');
    }

    public function actualizarItem(AgregarItemCarritoRequest $request, Pedido $pedido, PedidoItem $item): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEsCarrito($pedido);
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

        return redirect()->route('carritos.show', $pedido)->with('status', 'Línea actualizada.');
    }

    public function quitarItem(Request $request, Pedido $pedido, PedidoItem $item): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEsCarrito($pedido);
        $this->autorizarItem($pedido, $item);

        $item->delete();
        $pedido->recalcularTotal();

        return redirect()->route('carritos.show', $pedido)->with('status', 'Producto quitado.');
    }

    public function cerrar(
        CerrarCarritoRequest $request,
        Pedido $pedido,
        ProcesadorDeCobro $procesador,
        EmisionComprobanteService $emisor,
    ): RedirectResponse {
        $this->autorizar($request, $pedido);
        $this->asegurarEsCarrito($pedido);

        $destino = $request->validated('destino');
        $usuario = $request->user();

        if ($destino === 'inmediato' && $usuario->role === 'cajero_vendedor') {
            try {
                $factura = $procesador->cobrar(
                    $pedido,
                    $usuario,
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

            return redirect()->route('carritos.index')->with('status', 'Venta cobrada.');
        }

        $pedido->update([
            'estado' => $destino === 'inmediato' ? Pedido::ESTADO_EN_CAJA : Pedido::ESTADO_PROGRAMADO,
            'fecha_programada' => $destino === 'programado' ? $request->validated('fecha_programada') : null,
        ]);

        return redirect()->route('carritos.index')->with('status', 'Carrito cerrado.');
    }

    public function cancelar(Request $request, Pedido $pedido): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEsCarrito($pedido);

        $pedido->update(['estado' => Pedido::ESTADO_CANCELADO]);

        return redirect()->route('carritos.index')->with('status', 'Carrito cancelado.');
    }

    /**
     * Decide qué precio unitario usar para una línea del carrito:
     * 1. Si mandaron un precio especial a mano (y el usuario tiene permiso), ese gana siempre.
     * 2. Si no mandaron uno y la línea ya tenía un precio especial activo, depende de quién pregunta:
     *    al sumar cantidad de otro lugar (buscador/escáner) que no ve ni edita el precio de esa línea,
     *    se conserva el manual que ya tenía; al usar el "Actualizar" propio de la línea si dejaron el
     *    campo vacío a propósito, se entiende como "volver al automático" y no se conserva.
     * 3. Si no aplica nada de lo anterior, se calcula automático según el tramo de cantidad que
     *    corresponda (o el precio de catálogo si ninguno aplica).
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

    private function carritoAbierto(User $usuario): ?Pedido
    {
        return Pedido::query()
            ->where('vendedor_id', $usuario->id)
            ->where('estado', Pedido::ESTADO_CARRITO)
            ->latest()
            ->first();
    }

    private function crearCarrito(User $usuario): Pedido
    {
        return Pedido::create([
            'empresa_id' => $usuario->empresa_id,
            'vendedor_id' => $usuario->id,
            'cliente_nombre' => Pedido::CLIENTE_POR_DEFECTO,
            'estado' => Pedido::ESTADO_CARRITO,
        ]);
    }

    private function autorizar(Request $request, Pedido $pedido): void
    {
        if ($pedido->vendedor_id !== $request->user()->id) {
            abort(404);
        }
    }

    private function autorizarItem(Pedido $pedido, PedidoItem $item): void
    {
        if ($item->pedido_id !== $pedido->id) {
            abort(404);
        }
    }

    private function asegurarEsCarrito(Pedido $pedido): void
    {
        if (! $pedido->esCarrito()) {
            abort(404);
        }
    }
}
