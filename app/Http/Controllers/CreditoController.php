<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\PagoCredito;
use App\Models\Pedido;
use App\Services\Facturacion\ComprobantePdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CreditoController extends Controller
{
    public function index(Request $request): View
    {
        $empresaId = $request->user()->empresa_id;

        $porPagina = (int) $request->input('por_pagina', 10);

        if (! in_array($porPagina, [10, 50, 100], true)) {
            $porPagina = 10;
        }

        $fechaDesde = $request->input('fecha_desde', now()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));
        $fechaFiltradaManualmente = $request->has('fecha_desde') || $request->has('fecha_hasta');

        $clienteId = $request->filled('cliente_id') ? (int) $request->input('cliente_id') : null;
        $cliente = $clienteId ? Cliente::where('empresa_id', $empresaId)->find($clienteId) : null;

        $ventasFiadas = Pedido::query()
            ->where('empresa_id', $empresaId)
            ->where('monto_fiado', '>', 0)
            ->when($cliente, fn ($q) => $q->where('cliente_id', $cliente->id))
            ->when($fechaDesde, fn ($q) => $q->whereDate('cobrado_at', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($q) => $q->whereDate('cobrado_at', '<=', $fechaHasta))
            ->with(['cliente', 'vendedor', 'cajero'])
            ->orderByDesc('cobrado_at')
            ->paginate($porPagina)
            ->withQueryString();

        $pagosCliente = $cliente
            ? $cliente->pagosCredito()->with('usuario')->latest()->get()
            : collect();

        return view('creditos.index', [
            'ventasFiadas' => $ventasFiadas,
            'cliente' => $cliente,
            'pagosCliente' => $pagosCliente,
            'totalesGenerales' => $cliente ? null : $this->totalesGenerales($empresaId),
            'clientesParaBuscador' => Cliente::where('empresa_id', $empresaId)->orderBy('nombre')->get(['id', 'nombre', 'cuit']),
            'porPagina' => $porPagina,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'fechaFiltradaManualmente' => $fechaFiltradaManualmente,
        ]);
    }

    /**
     * Igual que Cliente::totalFiado()/totalPagadoCredito()/saldoPendiente()
     * pero sumado entre TODOS los clientes de la empresa — histórico, sin
     * filtrar por fecha (mismo criterio que esos métodos: el filtro de
     * fecha de esta pantalla es solo para la tabla de ventas fiadas, no
     * para estos totales). Se muestra únicamente cuando no hay un cliente
     * puntual filtrado (ahí ya está el desglose individual).
     *
     * @return array{totalFiado: float, totalPagado: float, saldoPendiente: float}
     */
    private function totalesGenerales(int $empresaId): array
    {
        $totalFiado = (float) Pedido::where('empresa_id', $empresaId)
            ->where('monto_fiado', '>', 0)
            ->sum('monto_fiado');

        $totalPagado = (float) PagoCredito::where('empresa_id', $empresaId)
            ->selectRaw('COALESCE(SUM(monto_efectivo + monto_tarjeta + monto_transferencia), 0) as total')
            ->value('total');

        return [
            'totalFiado' => $totalFiado,
            'totalPagado' => $totalPagado,
            'saldoPendiente' => round($totalFiado - $totalPagado, 2),
        ];
    }

    /**
     * La fecha de promesa de pago vive en `Cliente` (una por cliente, no por
     * venta) pero se edita desde cada línea de la tabla de ventas fiadas —
     * si el mismo cliente tiene varias ventas fiadas, todas muestran/editan
     * el mismo valor. Se puede cargar individual (este endpoint) o en lote
     * (`actualizarPromesaPagoMasiva`, tildando varias filas a la vez).
     */
    public function actualizarPromesaPago(Request $request, Cliente $cliente): RedirectResponse
    {
        $this->autorizarCliente($request, $cliente);

        $datos = $request->validate([
            'fecha_promesa_pago' => ['nullable', 'date'],
        ]);

        $cliente->update(['fecha_promesa_pago' => $datos['fecha_promesa_pago'] ?? null]);

        return back()->with('status', $datos['fecha_promesa_pago']
            ? 'Fecha de promesa de pago actualizada para '.$cliente->nombre.'.'
            : 'Se quitó la fecha de promesa de pago de '.$cliente->nombre.'.');
    }

    /**
     * Misma fecha para varios clientes elegidos a mano (checkboxes en la
     * tabla de ventas fiadas) — pensado para cuando varios clientes acuerdan
     * pagar el mismo día (ej. todos cobran el mismo día del mes).
     */
    public function actualizarPromesaPagoMasiva(Request $request): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id;

        $datos = $request->validate([
            'cliente_ids' => ['required', 'array', 'min:1'],
            'cliente_ids.*' => ['integer'],
            'fecha_promesa_pago' => ['required', 'date'],
        ]);

        $actualizados = Cliente::where('empresa_id', $empresaId)
            ->whereIn('id', $datos['cliente_ids'])
            ->update(['fecha_promesa_pago' => $datos['fecha_promesa_pago']]);

        return back()->with('status', "Fecha de promesa de pago asignada a {$actualizados} cliente(s).");
    }

    private function autorizarCliente(Request $request, Cliente $cliente): void
    {
        if ($cliente->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }
    }

    public function registrarPago(Request $request): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id;

        $datos = $request->validate([
            'cliente_id' => ['required', Rule::exists('clientes', 'id')->where('empresa_id', $empresaId)],
            'monto_efectivo' => ['nullable', 'numeric', 'min:0'],
            'monto_tarjeta' => ['nullable', 'numeric', 'min:0'],
            'monto_transferencia' => ['nullable', 'numeric', 'min:0'],
        ]);

        $montoEfectivo = (float) ($datos['monto_efectivo'] ?? 0);
        $montoTarjeta = (float) ($datos['monto_tarjeta'] ?? 0);
        $montoTransferencia = (float) ($datos['monto_transferencia'] ?? 0);

        if ($montoEfectivo <= 0 && $montoTarjeta <= 0 && $montoTransferencia <= 0) {
            return back()->withErrors(['monto_efectivo' => 'Cargá al menos un monto (efectivo, tarjeta o transferencia).'])->withInput();
        }

        $pago = PagoCredito::create([
            'empresa_id' => $empresaId,
            'cliente_id' => $datos['cliente_id'],
            'user_id' => $request->user()->id,
            // Nullable: un admin puede registrar un pago sin tener caja
            // abierta (nunca abre caja) — en ese caso el pago no queda
            // atado a ningún turno, simplemente no aparece en el cierre de
            // ninguna caja puntual.
            'caja_id' => $request->user()->cajaAbierta()?->id,
            'monto_efectivo' => $montoEfectivo,
            'monto_tarjeta' => $montoTarjeta,
            'monto_transferencia' => $montoTransferencia,
        ]);

        // Mismo patrón que después de cobrar una venta (pedido_comprobante_listo_id
        // en layouts.app): ofrece descargar el comprobante del pago recién hecho,
        // gateado por el mismo interruptor `mostrar_modal_comprobante`.
        session()->flash('pago_credito_comprobante_id', $pago->id);

        return redirect()->route('creditos.index', ['cliente_id' => $datos['cliente_id']])->with('status', 'Pago registrado.');
    }

    public function comprobantePagoPdf(Request $request, PagoCredito $pago, ComprobantePdfService $pdf): Response
    {
        $this->autorizarPago($request, $pago);

        $pago->load(['cliente', 'usuario']);

        return response($pdf->generarPagoCredito($pago), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->nombreArchivoPagoCredito($pago).'"',
        ]);
    }

    private function autorizarPago(Request $request, PagoCredito $pago): void
    {
        if ($pago->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }
    }
}
