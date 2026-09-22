<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Vista de admin sobre todas las cajas de la empresa (cualquier cajero o
 * cajero_vendedor) — el equivalente de `CajaSesionController` pero sin
 * acotar a "las mías". Comparten la misma vista de detalle
 * (`partials.detalle-caja`).
 */
class HistorialCajasController extends Controller
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

        $cajas = Caja::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->when($buscar !== '', fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$buscar}%")))
            ->when($fechaDesde, fn ($q) => $q->whereDate('abierta_at', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($q) => $q->whereDate('abierta_at', '<=', $fechaHasta))
            ->with('user')
            ->latest('abierta_at')
            ->paginate($porPagina)
            ->withQueryString();

        return view('cajas.index', [
            'cajas' => $cajas,
            'porPagina' => $porPagina,
            'buscar' => $buscar,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'fechaFiltradaManualmente' => $fechaFiltradaManualmente,
        ]);
    }

    public function show(Request $request, Caja $caja): View
    {
        if ($caja->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }

        return view('cajas.show', ['caja' => $caja]);
    }
}
