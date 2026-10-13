<?php

namespace App\Http\Controllers;

use App\Http\Requests\EditarItemsVentaRequest;
use App\Http\Requests\RegistrarCobroRequest;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\IntentoPagoMercadopago;
use App\Models\Pedido;
use App\Models\Producto;
use App\Services\Facturacion\EmisionComprobanteService;
use App\Services\MercadoPago\MercadoPagoQrService;
use App\Services\ProcesadorDeCobro;
use Illuminate\Http\JsonResponse;
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

    /**
     * Búsqueda en vivo para el buscador de "Editar pedido en caja" — antes
     * se mandaba el catálogo entero de la empresa (ver editar()). Con
     * empresas de varios miles de productos eso pesaba varios cientos de
     * KB en cada carga de esa pantalla.
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
            ->orderByRaw('codigo = ? desc', [$texto])
            ->orderBy('nombre')
            ->limit(8)
            ->get(['id', 'nombre', 'codigo', 'precio']);

        return response()->json($productos->map(fn (Producto $producto) => [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'codigo' => $producto->codigo,
            'precio' => (float) $producto->precio,
        ]));
    }

    public function show(Request $request, Pedido $pedido): View
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEnCaja($pedido);

        return view('caja.show', [
            'pedido' => $pedido->load(['items', 'vendedor']),
            'empresa' => $request->user()->empresa,
            'clientes' => Cliente::where('empresa_id', $request->user()->empresa_id)->where('activo', true)->orderBy('nombre')->get(),
            'intentoQrPendiente' => IntentoPagoMercadopago::pendienteParaPedido($pedido->id),
        ]);
    }

    public function cobrar(
        RegistrarCobroRequest $request,
        Pedido $pedido,
        ProcesadorDeCobro $procesador,
        EmisionComprobanteService $emisor,
        MercadoPagoQrService $qrService,
    ): RedirectResponse|View|JsonResponse {
        $this->autorizar($request, $pedido);
        $this->asegurarEnCaja($pedido);

        $montoMercadopago = (float) ($request->validated('monto_mercadopago') ?: 0);

        if ($montoMercadopago > 0) {
            return $this->iniciarCobroQr($request, $pedido, $montoMercadopago, $qrService);
        }

        try {
            $factura = $procesador->cobrar(
                $pedido,
                $request->user(),
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

        $empresa = $request->user()->empresa;

        // "Automático" imprime derecho sin preguntar, "preguntar" ofrece el
        // cartel de siempre, "sin_ticket" no hace nada de esto — ver
        // Empresa::comportamiento_impresion.
        if ($empresa->imprimeAutomaticamente() && ($urlImprimir = $pedido->urlComprobanteParaImprimir())) {
            return redirect($urlImprimir);
        }

        if ($empresa->preguntaAntesDeImprimir() && $pedido->tieneComprobanteImprimible()) {
            session()->flash('pedido_comprobante_listo_id', $pedido->id);
        }

        return redirect()->route('caja.index')->with('status', 'Pedido cobrado.');
    }

    /**
     * A diferencia del resto de los medios de pago, el QR de Mercado Pago
     * es asíncrono (el cliente paga desde su celular, puede tardar o no
     * completarse nunca) — acá NO se llama a ProcesadorDeCobro::cobrar(),
     * el pedido se queda tal cual está (sin stock/numero_venta tocados)
     * hasta que el webhook confirme el pago real.
     */
    private function iniciarCobroQr(RegistrarCobroRequest $request, Pedido $pedido, float $monto, MercadoPagoQrService $qrService): View|JsonResponse
    {
        try {
            $intento = $qrService->iniciarIntento(
                $request->user()->empresa,
                $pedido,
                $request->user(),
                $monto,
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
                'redirect_url' => route('caja.index'),
            ]);
        }

        return view('caja.esperando-pago-qr', ['intento' => $intento]);
    }

    public function estadoIntento(Request $request, IntentoPagoMercadopago $intento): JsonResponse
    {
        if ($intento->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }

        if ($intento->expirado()) {
            $intento->update(['estado' => IntentoPagoMercadopago::ESTADO_EXPIRADO]);
        }

        if ($intento->estado === IntentoPagoMercadopago::ESTADO_APROBADO) {
            $this->marcarComprobanteListoSiCorresponde($intento);
        }

        return response()->json(['estado' => $intento->estado]);
    }

    public function cancelarIntento(Request $request, IntentoPagoMercadopago $intento, MercadoPagoQrService $qrService): JsonResponse
    {
        if ($intento->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }

        try {
            $qrService->cancelarIntento($intento);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json(['estado' => $intento->estado]);
    }

    public function editar(Request $request, Pedido $pedido): View
    {
        $this->autorizar($request, $pedido);
        $this->asegurarEnCaja($pedido);

        return view('pedidos.editar-items', [
            'titulo' => 'Editar pedido en caja',
            'pedido' => $pedido->load('items'),
            'actionUrl' => route('caja.actualizarItems', $pedido),
            'volverUrl' => route('caja.show', $pedido),
            'buscarProductoUrl' => route('caja.buscarProducto'),
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

    /**
     * El cobro por QR se confirma via webhook, sin request del cajero de por
     * medio, asi que el flash de "pedido_comprobante_listo_id" (que dispara
     * el modal de impresion en layouts/app.blade.php) no se puede setear ahi
     * — se setea aca, en el primer polling del cajero que ve el intento ya
     * aprobado, para que el modal aparezca apenas vuelva a cargar una pagina.
     */
    private function marcarComprobanteListoSiCorresponde(IntentoPagoMercadopago $intento): void
    {
        $pedido = $intento->pedido?->load('factura');

        if (! $pedido) {
            return;
        }

        $tipoComprobante = $intento->montos_json['tipo_comprobante'] ?? null;
        $hayComprobante = $tipoComprobante === 'remito' || $pedido->factura?->estado === Factura::ESTADO_APROBADA;

        // El cobro con QR se confirma por webhook, sin que el cajero esté
        // mandando un request en ese momento — no hay una respuesta HTTP
        // ahí para redirigir derecho a imprimir como en los demás cobros,
        // así que acá "automático" se queda en no-op por ahora (igual que
        // "sin_ticket"); solo "preguntar" ofrece el cartel, como siempre.
        if ($hayComprobante && $pedido->empresa->preguntaAntesDeImprimir()) {
            session()->flash('pedido_comprobante_listo_id', $pedido->id);
        }
    }
}
