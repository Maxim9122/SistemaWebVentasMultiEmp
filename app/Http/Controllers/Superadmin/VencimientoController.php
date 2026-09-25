<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use Illuminate\View\View;

class VencimientoController extends Controller
{
    /**
     * Empresas activas con el abono vencido (30+ días desde que venció la
     * cobertura de su último pago) — se calcula en memoria porque
     * `vigenciaAbonoHasta()` depende de sumar el historial de pagos de cada
     * empresa (no es una columna que se pueda filtrar directo en SQL), y la
     * cantidad de empresas de la plataforma no amerita optimizar esto todavía.
     */
    public function index(): View
    {
        $vencidas = Empresa::where('estado', 'activa')
            ->with('pagosAbono')
            ->get()
            ->filter(fn (Empresa $empresa) => $empresa->abonoVencido())
            ->sortByDesc(fn (Empresa $empresa) => $empresa->diasVencidoAbono())
            ->values();

        return view('superadmin.vencimientos.index', ['empresas' => $vencidas]);
    }
}
