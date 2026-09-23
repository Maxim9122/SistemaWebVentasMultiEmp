<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgregarItemCarritoRequest;
use App\Http\Requests\CerrarCarritoRequest;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
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
 * A diferencia de la primera versión de este experimento, ahora SÍ opera
 * sobre un {pedido} explícito en la URL (igual que carritos.*) — hace
 * falta para soportar varios carritos abiertos en simultáneo del mismo
 * vendedor (permite_multiples_carritos), que la primera versión no cubría.
 */
class VentaReactController extends Controller
{
    public function index(): View
    {
        return view('venta-react.shell');
    }

    /** Catálogo + config: se pide una sola vez, no cambia al cambiar de carrito. */
    public function catalogo(Request $request): JsonResponse
    {
        $usuario = $request->user();

        return response()->json([
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
                'permiteMultiplesCarritos' => (bool) $usuario->empresa->permite_multiples_carritos,
                'ajustes' => [
                    'efectivo' => (float) $usuario->empresa->ajuste_efectivo_porcentaje,
                    'tarjeta' => (float) $usuario->empresa->ajuste_tarjeta_porcentaje,
                    'transferencia' => (float) $usuario->empresa->ajuste_transferencia_porcentaje,
                ],
            ],
        ]);
    }

    /** Lista de carritos abiertos del vendedor logueado, para los "chips" de arriba. */
    public function carritos(Request $request): JsonResponse
    {
        $carritos = Pedido::query()
            ->where('vendedor_id', $request->user()->id)
            ->where('estado', Pedido::ESTADO_CARRITO)
            ->orderBy('id')
            ->withCount('items')
            ->get()
            ->map(fn (Pedido $p) => [
                'id' => $p->id,
                'cliente_nombre' => $p->cliente_nombre,
                'total' => (float) $p->total,
                'items_count' => $p->items_count,
            ]);

        return response()->json(['carritos' => $carritos]);
    }

    /**
     * Mismo criterio que CarritoController::store(): si la empresa NO
     * permite varios carritos, "nuevo" no crea nada, devuelve el que ya
     * estaba abierto — el front lo trata igual (cambia a ese id) sin
     * necesitar saber por qué.
     */
    public function crearCarrito(Request $request): JsonResponse
    {
        $usuario = $request->user();

        if (! $usuario->empresa->permite_multiples_carritos) {
            $existente = Pedido::where('vendedor_id', $usuario->id)->where('estado', Pedido::ESTADO_CARRITO)->latest()->first();

            if ($existente) {
                return response()->json(['pedidoId' => $existente->id, 'reutilizado' => true]);
            }
        }

        $pedido = Pedido::create([
            'empresa_id' => $usuario->empresa_id,
            'vendedor_id' => $usuario->id,
            'cliente_nombre' => Pedido::CLIENTE_POR_DEFECTO,
            'estado' => Pedido::ESTADO_CARRITO,
        ]);

        return response()->json(['pedidoId' => $pedido->id, 'reutilizado' => false]);
    }

    public function estado(Request $request, Pedido $pedido): JsonResponse
    {
        $this->autorizar($request, $pedido);

        return response()->json($this->cuerpoCarrito($pedido));
    }

    public function agregarItem(AgregarItemCarritoRequest $request, Pedido $pedido): JsonResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEsCarrito($pedido);

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

        return response()->json($this->cuerpoCarrito($pedido));
    }

    public function actualizarItem(AgregarItemCarritoRequest $request, Pedido $pedido, PedidoItem $item): JsonResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEsCarrito($pedido);
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

        return response()->json($this->cuerpoCarrito($pedido));
    }

    public function quitarItem(Request $request, Pedido $pedido, PedidoItem $item): JsonResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEsCarrito($pedido);
        abort_if($item->pedido_id !== $pedido->id, 404);

        $item->delete();
        $pedido->recalcularTotal();

        return response()->json($this->cuerpoCarrito($pedido));
    }

    public function renombrarCliente(Request $request, Pedido $pedido): JsonResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEsCarrito($pedido);
        $request->validate(['cliente_nombre' => ['nullable', 'string', 'max:255']]);

        $nombre = trim((string) $request->input('cliente_nombre'));
        $pedido->update(['cliente_nombre' => $nombre !== '' ? $nombre : Pedido::CLIENTE_POR_DEFECTO]);

        return response()->json($this->cuerpoCarrito($pedido));
    }

    public function cerrar(
        CerrarCarritoRequest $request,
        Pedido $pedido,
        ProcesadorDeCobro $procesador,
        EmisionComprobanteService $emisor,
    ): JsonResponse {
        $this->autorizar($request, $pedido);
        $this->asegurarEsCarrito($pedido);

        $usuario = $request->user();
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

    private function cuerpoCarrito(Pedido $pedido): array
    {
        return [
            'pedido' => $this->serializarPedido($pedido),
            'items' => $this->serializarItems($pedido),
        ];
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

    private function autorizar(Request $request, Pedido $pedido): void
    {
        if ($pedido->vendedor_id !== $request->user()->id) {
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
