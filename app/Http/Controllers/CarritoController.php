<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgregarItemCarritoRequest;
use App\Http\Requests\CerrarCarritoRequest;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Factura;
use App\Models\IntentoPagoMercadopago;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\User;
use App\Services\Facturacion\EmisionComprobanteService;
use App\Services\LectorCodigoBalanza;
use App\Services\MercadoPago\MercadoPagoQrService;
use App\Services\ProcesadorDeCobro;
use Illuminate\Http\JsonResponse;
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

    /**
     * Búsqueda en vivo para el buscador de productos del carrito. Antes se
     * mandaba el catálogo entero (nombre/código/precio de cada producto) ya
     * armado en la página — con empresas de varios miles de productos eso
     * eran varios cientos de KB de JSON en cada carga de la pantalla, y en
     * cada "Agregar" (que recarga la página). Ahora se busca en el momento,
     * nunca se devuelven más de 8 resultados.
     */
    public function buscarProducto(Request $request): JsonResponse
    {
        $texto = trim((string) $request->input('q', ''));

        if ($texto === '') {
            return response()->json([]);
        }

        $productos = Producto::where('empresa_id', $request->user()->empresa_id)
            ->where('activo', true)
            ->where(function ($query) use ($texto) {
                $query->where('nombre', 'like', "%{$texto}%")
                    ->orWhere('codigo', 'like', "%{$texto}%");
            })
            // Si el texto es justo un código exacto (lector de código de
            // barras), que aparezca primero sin importar el orden alfabético.
            ->orderByRaw('codigo = ? desc', [$texto])
            ->orderBy('nombre')
            ->limit(8)
            ->get(['id', 'nombre', 'codigo', 'precio', 'unidad']);

        return response()->json($productos->map(fn (Producto $producto) => [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'codigo' => $producto->codigo,
            'precio' => (float) $producto->precio,
            'es_por_peso' => $producto->esPorPeso(),
        ]));
    }

    /**
     * Si el texto escaneado es un código "con peso o precio embebido" de
     * balanza (ver LectorCodigoBalanza) y matchea un producto "por peso"
     * de esta empresa, lo devuelve con el precio ya calculado para
     * agregarlo directo al carrito, sin que el cajero tenga que buscar
     * ni tipear nada — un código de producto normal, o una empresa que no
     * configuró esto, simplemente no matchean nunca y no afectan en nada
     * al buscador de siempre.
     */
    public function buscarCodigoBalanza(Request $request, LectorCodigoBalanza $lector): JsonResponse
    {
        $codigo = trim((string) $request->input('codigo', ''));
        $empresa = $request->user()->empresa;

        $resultado = $lector->parsear($empresa, $codigo);

        if (! $resultado) {
            return response()->json(['encontrado' => false]);
        }

        $producto = Producto::where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->where('codigo', $resultado['codigo_producto'])
            ->first(['id', 'nombre', 'precio', 'unidad']);

        // Solo para productos "por peso" a propósito: ese camino ya
        // descuenta cantidad=1 y toma precio_unitario tal cual, sin pedir
        // el permiso de "cambiar precio de venta" — es justo lo que hace
        // falta acá (el precio ya viene calculado por la balanza, no es
        // una excepción al precio normal). Para un producto que no es por
        // peso, se informa "no encontrado" y el cajero lo busca a mano.
        if (! $producto || ! $producto->esPorPeso()) {
            return response()->json(['encontrado' => false]);
        }

        $precio = $resultado['tipo'] === Empresa::BALANZA_VALOR_PESO
            ? round($resultado['valor'] * (float) $producto->precio, 2)
            : round($resultado['valor'], 2);

        return response()->json([
            'encontrado' => true,
            'producto_id' => $producto->id,
            'nombre' => $producto->nombre,
            'precio' => $precio,
        ]);
    }

    public function show(Request $request, Pedido $pedido): View|RedirectResponse
    {
        $this->autorizar($request, $pedido);

        if ($redirect = $this->redirigirSiNoEsCarritoVigente($request, $pedido)) {
            return $redirect;
        }

        $otrosCarritos = Pedido::query()
            ->where('vendedor_id', $request->user()->id)
            ->where('estado', Pedido::ESTADO_CARRITO)
            ->where('id', '!=', $pedido->id)
            ->get();

        return view('carritos.show', [
            'pedido' => $pedido->load('items.producto'),
            'otrosCarritos' => $otrosCarritos,
            'puedeCambiarPrecio' => $request->user()->puedeCambiarPrecioVenta(),
            'empresa' => $request->user()->empresa,
            'clientes' => Cliente::where('empresa_id', $request->user()->empresa_id)->where('activo', true)->orderBy('nombre')->get(),
            'intentoQrPendiente' => IntentoPagoMercadopago::pendienteParaPedido($pedido->id),
        ]);
    }

    public function renombrar(Request $request, Pedido $pedido): RedirectResponse
    {
        $this->autorizar($request, $pedido);

        if ($redirect = $this->redirigirSiNoEsCarritoVigente($request, $pedido)) {
            return $redirect;
        }

        $request->validate(['cliente_nombre' => ['nullable', 'string', 'max:255']]);

        $nombre = trim((string) $request->input('cliente_nombre'));

        $pedido->update(['cliente_nombre' => $nombre !== '' ? $nombre : Pedido::CLIENTE_POR_DEFECTO]);

        return redirect()->route('carritos.show', $pedido);
    }

    public function agregarItem(AgregarItemCarritoRequest $request, Pedido $pedido): RedirectResponse
    {
        $this->autorizar($request, $pedido);

        if ($redirect = $this->redirigirSiNoEsCarritoVigente($request, $pedido)) {
            return $redirect;
        }

        $producto = Producto::findOrFail($request->validated('producto_id'));
        $cantidad = $producto->esPorPeso() ? 1 : (int) $request->validated('cantidad');

        // Un producto por peso nunca se fusiona con una línea ya cargada
        // (cada pesada es un precio propio, calculado aparte por la
        // balanza) — si se fusionara, la segunda pesada pisaría el precio
        // de la primera en vez de sumarse, perdiendo esa venta.
        $itemExistente = $producto->esPorPeso() ? null : $pedido->items()->where('producto_id', $producto->id)->first();

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

        if ($redirect = $this->redirigirSiNoEsCarritoVigente($request, $pedido)) {
            return $redirect;
        }

        $this->autorizarItem($pedido, $item);

        $producto = $item->producto ?? new Producto(['precio' => $item->precio_original]);
        $cantidad = $producto->esPorPeso() ? 1 : (int) $request->validated('cantidad');
        $resolucion = $this->resolverPrecio(
            $request,
            $producto,
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

        if ($redirect = $this->redirigirSiNoEsCarritoVigente($request, $pedido)) {
            return $redirect;
        }

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
        MercadoPagoQrService $qrService,
    ): RedirectResponse|View|JsonResponse {
        $this->autorizar($request, $pedido);

        if ($redirect = $this->redirigirSiNoEsCarritoVigente($request, $pedido)) {
            return $redirect;
        }

        $destino = $request->validated('destino');
        $usuario = $request->user();

        if ($destino === 'inmediato' && $usuario->role === 'cajero_vendedor') {
            $montoMercadopago = (float) ($request->validated('monto_mercadopago') ?: 0);

            if ($montoMercadopago > 0) {
                try {
                    $intento = $qrService->iniciarIntento(
                        $usuario->empresa,
                        $pedido,
                        $usuario,
                        $montoMercadopago,
                        [
                            'monto_efectivo' => $request->validated('monto_efectivo'),
                            'monto_tarjeta' => $request->validated('monto_tarjeta'),
                            'monto_transferencia' => $request->validated('monto_transferencia'),
                            'monto_fiado' => $request->validated('monto_fiado'),
                            'tipo_comprobante' => $request->validated('tipo_comprobante'),
                            'cliente_id' => $request->validated('cliente_id'),
                            'cliente_nombre' => $request->validated('cliente_nombre'),
                            'cliente_cuit' => $request->validated('cliente_cuit'),
                            'cliente_telefono' => $request->validated('cliente_telefono'),
                            'tipo_factura' => $request->validated('tipo_factura'),
                            'cliente_condicion_iva' => $request->validated('cliente_condicion_iva'),
                        ],
                    );
                } catch (\RuntimeException $e) {
                    abort(409, $e->getMessage());
                }

                if ($request->wantsJson()) {
                    return response()->json([
                        'intento_id' => $intento->id,
                        'monto' => (float) $intento->monto,
                        'estado_url' => route('caja.mercadopago.estado', $intento),
                        'cancelar_url' => route('caja.mercadopago.cancelar', $intento),
                        'redirect_url' => route('carritos.index'),
                    ]);
                }

                return view('caja.esperando-pago-qr', ['intento' => $intento]);
            }

            try {
                $factura = $procesador->cobrar(
                    $pedido,
                    $usuario,
                    [
                        'efectivo' => $request->validated('monto_efectivo'),
                        'tarjeta' => $request->validated('monto_tarjeta'),
                        'transferencia' => $request->validated('monto_transferencia'),
                        'mercadopago' => 0,
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
                    $request->validated('cliente_condicion_iva'),
                );
            } catch (\RuntimeException $e) {
                return back()->withErrors(['stock' => $e->getMessage()]);
            }

            // Siempre se refresca, haya factura o no — cobrar() trabaja sobre
            // una copia del pedido bloqueada aparte (para el lock de fila),
            // nunca sobre esta instancia, así que sin esto el remito/tipo de
            // comprobante/estado que ve el resto de este método quedan
            // desactualizados (afecta a tieneComprobanteImprimible() de más
            // abajo, no solo a la emisión).
            $pedido->refresh();

            if ($factura) {
                $emisor->emitir($factura, $pedido);
            }

            // "Automático" imprime derecho sin preguntar, "preguntar" ofrece
            // el cartel de siempre, "sin_ticket" no hace nada de esto — ver
            // Empresa::comportamiento_impresion.
            if ($usuario->empresa->imprimeAutomaticamente() && ($urlImprimir = $pedido->urlComprobanteParaImprimir())) {
                return redirect($urlImprimir);
            }

            if ($usuario->empresa->preguntaAntesDeImprimir() && $pedido->tieneComprobanteImprimible()) {
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

        if ($redirect = $this->redirigirSiNoEsCarritoVigente($request, $pedido)) {
            return $redirect;
        }

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
        // Un producto por peso no tiene "precio de catálogo" que aplicar —
        // el precio es siempre el que se cargó a mano (ya calculado por la
        // balanza), sin necesidad del permiso para cambiar precio de venta
        // (no es una excepción al precio normal, es la única forma de
        // vender este tipo de producto).
        if ($producto->esPorPeso()) {
            return ['precio' => (float) $request->validated('precio_unitario'), 'manual' => true];
        }

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

    /**
     * El mismo usuario puede tener este carrito abierto en dos pestañas o
     * dos dispositivos a la vez (ej. PC y celular con la misma sesión de
     * cajero_vendedor) — si lo cerró/cobró/canceló desde uno, el otro sigue
     * mostrando la página vieja hasta que se recarga o se intenta una
     * acción. Antes eso tiraba un 404 crudo; ahora se trata como lo que es
     * (alguien más ya resolvió este carrito, no hay nada "no encontrado") y
     * se manda a un carrito nuevo y vigente para seguir vendiendo sin
     * fricción.
     */
    private function redirigirSiNoEsCarritoVigente(Request $request, Pedido $pedido): RedirectResponse|JsonResponse|null
    {
        if ($pedido->esCarrito()) {
            return null;
        }

        $mensaje = 'Este carrito ya se cerró — probablemente lo cobraste desde otro dispositivo o pestaña. Te armamos uno nuevo para seguir vendiendo.';

        if ($request->wantsJson()) {
            return response()->json(['redirect_url' => route('carritos.index'), 'message' => $mensaje], 409);
        }

        return redirect()->route('carritos.index')->with('status', $mensaje);
    }
}
