<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\PagoQrMercadoPago;
use App\Services\MercadoPago\PagoQrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Primer endpoint JSON de la app (el resto del sistema es POST + redirect).
 * Lo consume el fetch() del modal de QR en partials/formulario-cobro.blade.php.
 *
 * A propósito, el pago con QR cubre siempre el total completo del pedido —
 * no se combina con otros medios en la misma venta (evita tener que
 * sostener un pago parcial "a medias" mientras se espera la confirmación
 * asincrónica de Mercado Pago). El cajero puede elegir Mercado Pago O
 * dividir entre los otros medios, no las dos cosas a la vez.
 */
class PagoQrController extends Controller
{
    public function crear(Request $request, Pedido $pedido, PagoQrService $servicio): JsonResponse
    {
        $this->autorizarPedido($request, $pedido);

        if (! $request->user()->empresa->pagoQrHabilitado()) {
            return response()->json(['mensaje' => 'Esta empresa no tiene habilitado el cobro con QR de Mercado Pago.'], 422);
        }

        if (in_array($pedido->estado, [Pedido::ESTADO_COBRADO, Pedido::ESTADO_CANCELADO], true)) {
            return response()->json(['mensaje' => 'Este pedido ya fue procesado.'], 422);
        }

        $datos = $request->validate([
            'tipo_comprobante' => ['required', Rule::in(Pedido::TIPOS_COMPROBANTE)],
            'cliente_id' => ['nullable', Rule::exists('clientes', 'id')->where('empresa_id', $request->user()->empresa_id)],
            'cliente_nombre' => ['nullable', 'string', 'max:255'],
            'cliente_cuit' => ['nullable', 'string', 'max:20'],
            'cliente_telefono' => ['nullable', 'string', 'max:50'],
            'tipo_factura' => ['nullable', Rule::in(Pedido::TIPOS_FACTURA)],
        ]);

        try {
            $intento = $servicio->crearIntento(
                $pedido,
                $request->user(),
                $datos['tipo_comprobante'],
                $datos['cliente_id'] ?? null,
                (filled($datos['cliente_nombre'] ?? null) && filled($datos['cliente_cuit'] ?? null)) ? [
                    'nombre' => $datos['cliente_nombre'],
                    'cuit' => $datos['cliente_cuit'],
                    'telefono' => $datos['cliente_telefono'] ?? null,
                ] : null,
                $datos['tipo_factura'] ?? null,
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['mensaje' => 'No se pudo generar el QR de Mercado Pago. Probá de nuevo en unos segundos.'], 500);
        }

        $qr = $servicio->generarQr($intento);

        if (! $qr) {
            return response()->json(['mensaje' => 'No se pudo generar el QR de Mercado Pago. Probá de nuevo en unos segundos.'], 500);
        }

        return response()->json([
            'intento_id' => $intento->id,
            'qr' => $qr,
            'expira_en' => $intento->expira_at->toIso8601String(),
        ]);
    }

    public function estado(Request $request, PagoQrMercadoPago $intento): JsonResponse
    {
        $this->autorizarIntento($request, $intento);

        if ($intento->expirado()) {
            $intento->update(['estado' => PagoQrMercadoPago::ESTADO_EXPIRADO]);
        }

        return response()->json([
            'estado' => $intento->estado,
            'pagado' => $intento->estado === PagoQrMercadoPago::ESTADO_APROBADO,
            'redirect' => $intento->estado === PagoQrMercadoPago::ESTADO_APROBADO
                ? route('ventas.show', $intento->pedido_id)
                : null,
        ]);
    }

    public function cancelar(Request $request, PagoQrMercadoPago $intento, PagoQrService $servicio): JsonResponse
    {
        $this->autorizarIntento($request, $intento);

        $servicio->cancelar($intento);

        return response()->json(['estado' => $intento->fresh()->estado]);
    }

    private function autorizarPedido(Request $request, Pedido $pedido): void
    {
        if ($pedido->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }
    }

    private function autorizarIntento(Request $request, PagoQrMercadoPago $intento): void
    {
        if ($intento->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }
    }
}
