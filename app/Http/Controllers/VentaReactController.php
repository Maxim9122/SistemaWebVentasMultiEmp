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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Experimento (rama `experimento/venta-react`, no tocar producción): el
 * mismo proceso de armar y cerrar una venta que ya existe en
 * `CarritoController`, pero servido como JSON para un front en React en vez
 * de forms + redirect de página completa. A propósito NO reusa
 * `CarritoController` (sus métodos de armado de precio son privados) — para
 * un prototipo que todavía puede tirarse, duplicar esa poca lógica es menos
 * riesgo que refactorizar el controller que ya está en producción. Si esto
 * se termina llevando a serio, ahí sí vale la pena extraer un servicio
 * compartido.
 *
 * Siempre opera sobre "mi carrito activo ahora" (nunca pide un {pedido} en
 * la URL) — más simple para un front que solo necesita una pantalla fija.
 */
class VentaReactController extends Controller
{
    public function index(): View
    {
        return view('venta-react.shell');
    }

    public function estado(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $pedido = $this->carritoActivo($usuario);

        return response()->json([
            'pedido' => $this->serializarPedido($pedido),
            'items' => $this->serializarItems($pedido),
            'productos' => Producto::where('empresa_id', $usuario->empresa_id)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'codigo', 'precio']),
            'clientes' => Cliente::where('empresa_id', $usuario->empresa_id)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'cuit']),
            'config' => [
                'rol' => $usuario->role,
                'puedeCambiarPrecio' => $usuario->puedeCambiarPrecioVenta(),
                'permiteFiado' => (bool) $usuario->empresa->permite_fiado,
                'ajustes' => [
                    'efectivo' => (float) $usuario->empresa->ajuste_efectivo_porcentaje,
                    'tarjeta' => (float) $usuario->empresa->ajuste_tarjeta_porcentaje,
                    'transferencia' => (float) $usuario->empresa->ajuste_transferencia_porcentaje,
                ],
            ],
        ]);
    }

    public function agregarItem(AgregarItemCarritoRequest $request): JsonResponse
    {
        $usuario = $request->user();
        $pedido = $this->carritoActivo($usuario);

        $producto = Producto::findOrFail($request->validated('producto_id'));
        $cantidad = (int) $request->validated('cantidad');
        $itemExistente = $pedido->items()->where('producto_id', $producto->id)->first();

        if ($itemExistente) {
            $nuevaCantidad = $itemExistente->cantidad + $cantidad;
            $resolucion = $this->resolverPrecio($request, $producto, $nuevaCantidad, (bool) $itemExistente->precio_manual, (float) $itemExistente->precio_unitario, true);

            $itemExistente->update([
                'cantidad' => $nuevaCantidad,
                'precio_unitario' => $resolucion['precio'],
                'precio_manual' => $resolucion['manual'],
                'subtotal' => round($nuevaCantidad * $resolucion['precio'], 2),
            ]);
        } else {
            $resolucion = $this->resolverPrecio($request, $producto, $cantidad, false, (float) $producto->precio, false);

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

        return $this->respuestaCarrito($pedido);
    }

    public function actualizarItem(AgregarItemCarritoRequest $request, PedidoItem $item): JsonResponse
    {
        $usuario = $request->user();
        $pedido = $this->carritoActivo($usuario);
        abort_if($item->pedido_id !== $pedido->id, 404);

        $cantidad = (int) $request->validated('cantidad');
        $resolucion = $this->resolverPrecio($request, $item->producto ?? new Producto(['precio' => $item->precio_original]), $cantidad, (bool) $item->precio_manual, (float) $item->precio_unitario, false);

        $item->update([
            'cantidad' => $cantidad,
            'precio_unitario' => $resolucion['precio'],
            'precio_manual' => $resolucion['manual'],
            'subtotal' => round($cantidad * $resolucion['precio'], 2),
        ]);

        $pedido->recalcularTotal();

        return $this->respuestaCarrito($pedido);
    }

    public function quitarItem(Request $request, PedidoItem $item): JsonResponse
    {
        $pedido = $this->carritoActivo($request->user());
        abort_if($item->pedido_id !== $pedido->id, 404);

        $item->delete();
        $pedido->recalcularTotal();

        return $this->respuestaCarrito($pedido);
    }

    public function renombrarCliente(Request $request): JsonResponse
    {
        $request->validate(['cliente_nombre' => ['nullable', 'string', 'max:255']]);

        $pedido = $this->carritoActivo($request->user());
        $nombre = trim((string) $request->input('cliente_nombre'));
        $pedido->update(['cliente_nombre' => $nombre !== '' ? $nombre : Pedido::CLIENTE_POR_DEFECTO]);

        return response()->json(['pedido' => $this->serializarPedido($pedido)]);
    }

    public function cerrar(
        CerrarCarritoRequest $request,
        ProcesadorDeCobro $procesador,
        EmisionComprobanteService $emisor,
    ): JsonResponse {
        $usuario = $request->user();
        $pedido = $this->carritoActivo($usuario);
        $destino = $request->validated('destino');

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
                return response()->json(['message' => $e->getMessage(), 'errors' => ['stock' => [$e->getMessage()]]], 422);
            }

            if ($factura) {
                $pedido->refresh();
                $emisor->emitir($factura, $pedido);
            }

            return response()->json([
                'ok' => true,
                'pedidoId' => $pedido->id,
                'redirect' => route('ventas.show', $pedido),
            ]);
        }

        // Vendedor (no cajero_vendedor): manda el carrito a Caja, no cobra acá.
        $pedido->update(['estado' => Pedido::ESTADO_EN_CAJA]);

        return response()->json(['ok' => true, 'redirect' => route('carritos.index')]);
    }

    private function respuestaCarrito(Pedido $pedido): JsonResponse
    {
        return response()->json([
            'pedido' => $this->serializarPedido($pedido),
            'items' => $this->serializarItems($pedido),
        ]);
    }

    private function serializarPedido(Pedido $pedido): array
    {
        return [
            'id' => $pedido->id,
            'cliente_nombre' => $pedido->cliente_nombre,
            'total' => (float) $pedido->total,
        ];
    }

    private function serializarItems(Pedido $pedido): array
    {
        return $pedido->items()->orderBy('id')->get()->map(fn (PedidoItem $item) => [
            'id' => $item->id,
            'producto_id' => $item->producto_id,
            'nombre_producto' => $item->nombre_producto,
            'cantidad' => $item->cantidad,
            'precio_unitario' => (float) $item->precio_unitario,
            'subtotal' => (float) $item->subtotal,
            'precio_manual' => (bool) $item->precio_manual,
        ])->all();
    }

    /** Misma lógica que CarritoController::resolverPrecio() — ver nota de clase. */
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

    /** Mismo criterio que CarritoController::carritoAbierto()/crearCarrito(). */
    private function carritoActivo(User $usuario): Pedido
    {
        $pedido = Pedido::query()
            ->where('vendedor_id', $usuario->id)
            ->where('estado', Pedido::ESTADO_CARRITO)
            ->latest()
            ->first();

        return $pedido ?? Pedido::create([
            'empresa_id' => $usuario->empresa_id,
            'vendedor_id' => $usuario->id,
            'cliente_nombre' => Pedido::CLIENTE_POR_DEFECTO,
            'estado' => Pedido::ESTADO_CARRITO,
        ]);
    }
}
