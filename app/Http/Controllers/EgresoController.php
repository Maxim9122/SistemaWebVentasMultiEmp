<?php

namespace App\Http\Controllers;

use App\Models\Egreso;
use App\Models\MotivoEgreso;
use App\Models\Producto;
use App\Services\ProcesadorDeCobro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EgresoController extends Controller
{
    public function index(Request $request): View
    {
        $porPagina = (int) $request->input('por_pagina', 10);

        if (! in_array($porPagina, [10, 50, 100], true)) {
            $porPagina = 10;
        }

        $fechaDesde = $request->input('fecha_desde', now()->format('Y-m-d'));
        $fechaHasta = $request->input('fecha_hasta', now()->format('Y-m-d'));
        $fechaFiltradaManualmente = $request->has('fecha_desde') || $request->has('fecha_hasta');

        $egresos = Egreso::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->when($fechaDesde, fn ($q) => $q->whereDate('created_at', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($q) => $q->whereDate('created_at', '<=', $fechaHasta))
            ->with(['motivo', 'usuario', 'proveedor', 'beneficiarioUsuario', 'producto', 'caja'])
            ->latest()
            ->paginate($porPagina)
            ->withQueryString();

        return view('egresos.index', [
            'egresos' => $egresos,
            'porPagina' => $porPagina,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'fechaFiltradaManualmente' => $fechaFiltradaManualmente,
        ]);
    }

    public function store(Request $request, ProcesadorDeCobro $procesador): RedirectResponse
    {
        $usuario = $request->user();
        $caja = $usuario->cajaAbierta();

        if (! $caja) {
            return back()->withErrors(['egreso' => 'Tenés que abrir tu caja antes de cargar un egreso.']);
        }

        $empresaId = $usuario->empresa_id;

        $datos = $request->validate([
            'motivo_egreso_id' => [
                'required',
                Rule::exists('motivos_egreso', 'id')->where('empresa_id', $empresaId)->where('activo', true),
            ],
            'producto_id' => [
                'nullable',
                Rule::exists('productos', 'id')->where('empresa_id', $empresaId)->where('activo', true),
            ],
            'cantidad' => ['nullable', 'integer', 'min:1'],
            'proveedor_id' => [
                'nullable',
                Rule::exists('proveedores', 'id')->where('empresa_id', $empresaId),
            ],
            'beneficiario_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('empresa_id', $empresaId),
            ],
            'beneficiario_nombre' => ['nullable', 'string', 'max:255'],
            'monto_efectivo' => ['nullable', 'numeric', 'min:0'],
            'monto_transferencia' => ['nullable', 'numeric', 'min:0'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ]);

        $motivo = MotivoEgreso::find($datos['motivo_egreso_id']);
        $proveedorId = $datos['proveedor_id'] ?? null;
        $beneficiarioUserId = $datos['beneficiario_user_id'] ?? null;

        // Un motivo "solo proveedores" no tiene sentido con un beneficiario de
        // staff, y uno "requiere producto" (consumo) no tiene proveedor — se
        // valida acá además de restringirlo en el buscador, por si alguien
        // arma el POST a mano.
        if ($motivo->solo_proveedores && $beneficiarioUserId) {
            return back()->withErrors(['beneficiario_user_id' => 'Este motivo solo admite un proveedor como beneficiario.'])->withInput();
        }

        if ($motivo->requiere_producto && $proveedorId) {
            return back()->withErrors(['proveedor_id' => 'Este motivo no admite un proveedor como beneficiario.'])->withInput();
        }

        $beneficiarioNombre = (! $proveedorId && ! $beneficiarioUserId)
            ? ($datos['beneficiario_nombre'] ?? null)
            : null;

        $datosComunes = [
            'empresa_id' => $empresaId,
            'caja_id' => $caja->id,
            'user_id' => $usuario->id,
            'motivo_egreso_id' => $motivo->id,
            'proveedor_id' => $proveedorId,
            'beneficiario_user_id' => $beneficiarioUserId,
            'beneficiario_nombre' => $beneficiarioNombre,
            'descripcion' => $datos['descripcion'] ?? null,
        ];

        if ($motivo->requiere_producto) {
            if (! ($datos['producto_id'] ?? null) || ! ($datos['cantidad'] ?? null)) {
                return back()->withErrors(['producto_id' => 'Elegí el producto y la cantidad consumida.'])->withInput();
            }

            $empresa = $usuario->empresa;
            $producto = Producto::findOrFail($datos['producto_id']);
            $cantidad = (int) $datos['cantidad'];
            // Precio de catálogo (sin tramos por cantidad, esto no es una
            // venta) con el descuento de precio empleado si está configurado.
            $precioAplicado = $empresa->precioEmpleadoPara((float) $producto->precio);
            $monto = round($precioAplicado * $cantidad, 2);

            try {
                DB::transaction(function () use ($empresa, $procesador, $producto, $cantidad, $precioAplicado, $monto, $datosComunes) {
                    if ($empresa->controla_stock) {
                        $procesador->descontarStockDeItems([
                            ['producto_id' => $producto->id, 'cantidad' => $cantidad],
                        ]);
                    }

                    Egreso::create([
                        ...$datosComunes,
                        'producto_id' => $producto->id,
                        'cantidad' => $cantidad,
                        'precio_unitario_aplicado' => $precioAplicado,
                        'monto_efectivo' => $monto,
                        'monto_transferencia' => 0,
                    ]);
                });
            } catch (\RuntimeException $e) {
                return back()->withErrors(['producto_id' => $e->getMessage()])->withInput();
            }

            return back()->with('status', 'Consumo registrado y stock descontado.');
        }

        $montoEfectivo = (float) ($datos['monto_efectivo'] ?? 0);
        $montoTransferencia = (float) ($datos['monto_transferencia'] ?? 0);

        if ($montoEfectivo <= 0 && $montoTransferencia <= 0) {
            return back()->withErrors(['monto_efectivo' => 'Cargá al menos un monto (efectivo o transferencia).'])->withInput();
        }

        Egreso::create([
            ...$datosComunes,
            'monto_efectivo' => $montoEfectivo,
            'monto_transferencia' => $montoTransferencia,
        ]);

        return back()->with('status', 'Egreso registrado.');
    }
}
