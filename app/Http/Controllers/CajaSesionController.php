<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Apertura/cierre de la caja física de un cajero o cajero_vendedor — no
 * confundir con `CajaController` (la cola de pedidos `en_caja` esperando
 * cobro). Cada usuario tiene como mucho una caja abierta a la vez.
 */
class CajaSesionController extends Controller
{
    public function show(Request $request): View
    {
        $usuario = $request->user();
        $caja = $usuario->cajaAbierta();

        return view('caja-sesion.show', [
            'caja' => $caja,
            'ultimasCerradas' => $caja ? collect() : Caja::where('user_id', $usuario->id)
                ->whereNotNull('cerrada_at')
                ->latest('cerrada_at')
                ->limit(5)
                ->get(),
        ]);
    }

    public function detalle(Request $request, Caja $caja): View
    {
        if ($caja->user_id !== $request->user()->id) {
            abort(404);
        }

        return view('caja-sesion.detalle', ['caja' => $caja]);
    }

    public function abrir(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        if ($usuario->cajaAbierta()) {
            return back()->withErrors(['monto_apertura' => 'Ya tenés una caja abierta.']);
        }

        $datos = $request->validate([
            'monto_apertura' => ['required', 'numeric', 'min:0'],
            'nota_apertura' => ['nullable', 'string', 'max:500'],
        ]);

        Caja::create([
            'empresa_id' => $usuario->empresa_id,
            'user_id' => $usuario->id,
            'abierta_at' => now(),
            'monto_apertura' => $datos['monto_apertura'],
            'nota_apertura' => $datos['nota_apertura'] ?? null,
        ]);

        return redirect()->route('caja-sesion.show')->with('status', 'Caja abierta.');
    }

    public function cerrar(Request $request): RedirectResponse
    {
        $usuario = $request->user();
        $caja = $usuario->cajaAbierta();

        if (! $caja) {
            return redirect()->route('caja-sesion.show');
        }

        $datos = $request->validate([
            'monto_cierre_declarado' => ['required', 'numeric', 'min:0'],
            'nota_cierre' => ['nullable', 'string', 'max:500'],
        ]);

        $caja->update([
            'cerrada_at' => now(),
            'monto_cierre_declarado' => $datos['monto_cierre_declarado'],
            'nota_cierre' => $datos['nota_cierre'] ?? null,
        ]);

        return redirect()->route('caja-sesion.show')->with('status', 'Caja cerrada.');
    }
}
