<?php

namespace App\Http\Controllers;

use App\Http\Requests\EditarItemsVentaRequest;
use App\Models\Pedido;
use App\Models\Producto;
use App\Services\ProcesadorDeCobro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PedidoController extends Controller
{
    public function index(Request $request): View
    {
        $porPagina = (int) $request->input('por_pagina', 10);

        if (! in_array($porPagina, [10, 50, 100], true)) {
            $porPagina = 10;
        }

        $buscar = trim((string) $request->input('buscar', ''));
        $fechaDesde = $request->input('fecha_desde', now()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));
        $fechaFiltradaManualmente = $request->has('fecha_desde') || $request->has('fecha_hasta');

        $pedidos = Pedido::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->where('estado', Pedido::ESTADO_PROGRAMADO)
            ->when($buscar !== '', fn ($q) => $q->where('cliente_nombre', 'like', "%{$buscar}%"))
            ->when($fechaDesde, fn ($q) => $q->whereDate('fecha_programada', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($q) => $q->whereDate('fecha_programada', '<=', $fechaHasta))
            ->with('vendedor')
            ->orderBy('fecha_programada')
            ->paginate($porPagina)
            ->withQueryString();

        return view('pedidos.index', [
            'pedidos' => $pedidos,
            'porPagina' => $porPagina,
            'buscar' => $buscar,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'fechaFiltradaManualmente' => $fechaFiltradaManualmente,
        ]);
    }

    public function show(Request $request, Pedido $pedido): View
    {
        $this->autorizar($request, $pedido);
        $this->asegurarProgramado($pedido);

        return view('pedidos.show', [
            'pedido' => $pedido->load(['items', 'vendedor']),
        ]);
    }

    public function pasarACaja(Request $request, Pedido $pedido): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarProgramado($pedido);

        $pedido->update(['estado' => Pedido::ESTADO_EN_CAJA]);

        return redirect()->route('pedidos.index')->with('status', 'Pedido pasado a caja.');
    }

    public function cancelar(Request $request, Pedido $pedido): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarProgramado($pedido);

        $pedido->update(['estado' => Pedido::ESTADO_CANCELADO]);

        return redirect()->route('pedidos.index')->with('status', 'Pedido cancelado.');
    }

    public function editar(Request $request, Pedido $pedido): View
    {
        $this->autorizar($request, $pedido);
        $this->asegurarProgramado($pedido);

        return view('pedidos.editar-items', [
            'titulo' => 'Editar pedido programado',
            'pedido' => $pedido->load('items'),
            'productos' => Producto::where('empresa_id', $request->user()->empresa_id)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(),
            'actionUrl' => route('pedidos.actualizarItems', $pedido),
            'volverUrl' => route('pedidos.show', $pedido),
            'puedeCambiarPrecio' => $request->user()->puedeCambiarPrecioVenta(),
        ]);
    }

    public function actualizarItems(EditarItemsVentaRequest $request, Pedido $pedido, ProcesadorDeCobro $procesador): RedirectResponse
    {
        $this->autorizar($request, $pedido);
        $this->asegurarProgramado($pedido);

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

        return redirect()->route('pedidos.show', $pedido)->with('status', 'Pedido actualizado.');
    }

    private function autorizar(Request $request, Pedido $pedido): void
    {
        if ($pedido->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }
    }

    private function asegurarProgramado(Pedido $pedido): void
    {
        if (! $pedido->esProgramado()) {
            abort(404);
        }
    }
}
