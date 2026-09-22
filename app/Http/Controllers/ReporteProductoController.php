<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReporteProductoController extends Controller
{
    public function index(Request $request): View
    {
        $porPagina = (int) $request->input('por_pagina', 10);

        if (! in_array($porPagina, [10, 50, 100], true)) {
            $porPagina = 10;
        }

        $productoId = $request->filled('producto_id') ? (int) $request->input('producto_id') : null;
        $fechaDesde = $request->input('fecha_desde', now()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));
        $fechaFiltradaManualmente = $request->has('fecha_desde') || $request->has('fecha_hasta');

        $producto = null;
        $items = null;
        $totales = null;

        if ($productoId) {
            $producto = Producto::where('empresa_id', $request->user()->empresa_id)->find($productoId);
        }

        if ($producto) {
            // Join directo (en vez de whereHas) porque además se usa para ordenar
            // por la fecha real de la venta (pedidos.cobrado_at) — una venta vieja
            // editada hoy recrea sus PedidoItem con id/created_at de hoy
            // (editarItems() borra y vuelve a crear), así que ordenar por el propio
            // item daría un orden cronológico incorrecto.
            $baseQuery = PedidoItem::query()
                ->join('pedidos', 'pedidos.id', '=', 'pedido_items.pedido_id')
                ->where('pedido_items.producto_id', $producto->id)
                ->where('pedidos.empresa_id', $request->user()->empresa_id)
                ->where('pedidos.estado', Pedido::ESTADO_COBRADO)
                ->when($fechaDesde, fn ($q) => $q->whereDate('pedidos.cobrado_at', '>=', $fechaDesde))
                ->when($fechaHasta, fn ($q) => $q->whereDate('pedidos.cobrado_at', '<=', $fechaHasta));

            $items = (clone $baseQuery)
                ->with(['pedido.items', 'pedido.vendedor', 'pedido.cajero', 'pedido.factura'])
                ->orderByDesc('pedidos.cobrado_at')
                ->select('pedido_items.*')
                ->paginate($porPagina)
                ->withQueryString();

            $totales = (clone $baseQuery)
                ->selectRaw('COALESCE(SUM(pedido_items.cantidad), 0) as cantidad_total, COALESCE(SUM(pedido_items.subtotal), 0) as importe_total')
                ->first();
        }

        return view('ventas.reporte-producto', [
            'productosParaBuscador' => Producto::where('empresa_id', $request->user()->empresa_id)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'codigo']),
            'producto' => $producto,
            'productoId' => $productoId,
            'items' => $items,
            'totales' => $totales,
            'porPagina' => $porPagina,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'fechaFiltradaManualmente' => $fechaFiltradaManualmente,
        ]);
    }
}
