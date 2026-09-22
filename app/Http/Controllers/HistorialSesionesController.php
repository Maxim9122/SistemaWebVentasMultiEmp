<?php

namespace App\Http\Controllers;

use App\Models\SesionUsuario;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistorialSesionesController extends Controller
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

        $sesiones = SesionUsuario::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->when($buscar !== '', fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$buscar}%")))
            ->when($fechaDesde, fn ($q) => $q->whereDate('login_at', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($q) => $q->whereDate('login_at', '<=', $fechaHasta))
            ->with('user')
            ->orderByDesc('login_at')
            ->paginate($porPagina)
            ->withQueryString();

        return view('staff.sesiones', [
            'sesiones' => $sesiones,
            'porPagina' => $porPagina,
            'buscar' => $buscar,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'fechaFiltradaManualmente' => $fechaFiltradaManualmente,
        ]);
    }
}
