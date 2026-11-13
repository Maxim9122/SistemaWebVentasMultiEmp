<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgregarItemCargaRapidaRequest;
use App\Models\Empresa;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * "Agregar al carrito" rápido desde la lista de Productos (botón por fila,
 * pide cantidad/precio en un modal) — a propósito un controller aparte en
 * vez de sumarle esto a CarritoController/PresupuestoController: acá no se
 * conoce de antemano a qué Pedido va el item (se resuelve solo, "el carrito
 * abierto" o "el presupuesto en curso" de este vendedor, igual que ya hace
 * CarritoController::index() para no abrir uno nuevo en cada click), así
 * que no encaja con agregarItem({pedido}) de esos controllers, que esperan
 * el pedido ya resuelto en la URL.
 *
 * La lógica de abajo (fusionar con un item ya cargado del mismo producto,
 * resolver el precio según el permiso de la empresa/usuario, crear el
 * pedido si hace falta) es la misma que ya usan CarritoController y
 * PresupuestoController — se duplica a propósito, mismo criterio que esos
 * dos entre sí: es la tercera copia de algo ya duplicado dos veces, no una
 * abstracción nueva no pedida, y evita tocar el código de venta/presupuesto
 * ya en producción para agregar este atajo.
 */
class ProductoCargaRapidaController extends Controller
{
    public function agregar(AgregarItemCargaRapidaRequest $request): JsonResponse
    {
        $usuario = $request->user();
        $producto = Producto::where('empresa_id', $usuario->empresa_id)->findOrFail($request->validated('producto_id'));
        $tipo = $request->validated('tipo');

        $pedido = $tipo === 'presupuesto'
            ? ($this->presupuestoEnCurso($usuario) ?? $this->crearPresupuesto($usuario))
            : ($this->carritoAbierto($usuario) ?? $this->crearCarrito($usuario));

        $cantidad = $producto->esPorPeso() ? 1 : (int) $request->validated('cantidad');

        // Mismo motivo que en CarritoController::agregarItem(): un producto
        // por peso nunca se fusiona con una línea ya cargada.
        $itemExistente = $producto->esPorPeso() ? null : $pedido->items()->where('producto_id', $producto->id)->first();

        if ($itemExistente) {
            $nuevaCantidad = $itemExistente->cantidad + $cantidad;
            $resolucion = $this->resolverPrecio(
                $request,
                $producto,
                $nuevaCantidad,
                (bool) $itemExistente->precio_manual,
                (float) $itemExistente->precio_unitario,
                conservarManualSiNoSeEnviaPrecio: true,
            );

            $itemExistente->update([
                'cantidad' => $nuevaCantidad,
                'precio_unitario' => $resolucion['precio'],
                'precio_manual' => $resolucion['manual'],
                'subtotal' => round($nuevaCantidad * $resolucion['precio'], 2),
            ]);
        } else {
            $resolucion = $this->resolverPrecio($request, $producto, $cantidad, false, (float) $producto->precio, conservarManualSiNoSeEnviaPrecio: false);

            PedidoItem::create([
                'pedido_id' => $pedido->id,
                'producto_id' => $producto->id,
                'nombre_producto' => $producto->nombre,
                'cantidad' => $cantidad,
                'precio_original' => $producto->precio,
                'precio_unitario' => $resolucion['precio'],
                'precio_manual' => $resolucion['manual'],
                'subtotal' => round($cantidad * $resolucion['precio'], 2),
            ]);
        }

        $pedido->recalcularTotal();

        return response()->json([
            'mensaje' => $tipo === 'presupuesto'
                ? 'Producto agregado al presupuesto.'
                : 'Producto agregado al carrito.',
            'tipo' => $tipo,
            'pedido_id' => $pedido->id,
        ]);
    }

    /**
     * @return array{precio: float, manual: bool}
     */
    private function resolverPrecio(
        AgregarItemCargaRapidaRequest $request,
        Producto $producto,
        int $cantidad,
        bool $eraManual,
        float $precioManualPrevio,
        bool $conservarManualSiNoSeEnviaPrecio,
    ): array {
        if ($producto->esPorPeso()) {
            return ['precio' => (float) $request->validated('precio_unitario'), 'manual' => true];
        }

        $precioSolicitado = $request->validated('precio_unitario');
        $puedeCambiar = $request->user()->puedeCambiarPrecioVenta();

        if ($puedeCambiar && filled($precioSolicitado)) {
            return ['precio' => (float) $precioSolicitado, 'manual' => true];
        }

        if ($puedeCambiar && $conservarManualSiNoSeEnviaPrecio && $eraManual) {
            return ['precio' => $precioManualPrevio, 'manual' => true];
        }

        return ['precio' => $producto->precioParaCantidad($cantidad)['precio'], 'manual' => false];
    }

    private function carritoAbierto(User $usuario): ?Pedido
    {
        return Pedido::query()
            ->where('vendedor_id', $usuario->id)
            ->where('estado', Pedido::ESTADO_CARRITO)
            ->latest()
            ->first();
    }

    private function crearCarrito(User $usuario): Pedido
    {
        return Pedido::create([
            'empresa_id' => $usuario->empresa_id,
            'vendedor_id' => $usuario->id,
            'cliente_nombre' => Pedido::CLIENTE_POR_DEFECTO,
            'estado' => Pedido::ESTADO_CARRITO,
        ]);
    }

    /**
     * Mismo criterio que carritoAbierto(): mientras se sigan agregando
     * productos en modo "Presupuesto" desde este botón rápido, todos entran
     * al mismo presupuesto — recién se crea uno nuevo si no hay ninguno de
     * este vendedor todavía en estado presupuesto.
     */
    private function presupuestoEnCurso(User $usuario): ?Pedido
    {
        return Pedido::query()
            ->where('vendedor_id', $usuario->id)
            ->where('estado', Pedido::ESTADO_PRESUPUESTO)
            ->latest()
            ->first();
    }

    private function crearPresupuesto(User $usuario): Pedido
    {
        return DB::transaction(function () use ($usuario) {
            $empresa = Empresa::where('id', $usuario->empresa_id)->lockForUpdate()->first();
            $numero = $empresa->ultimo_numero_presupuesto + 1;
            $empresa->update(['ultimo_numero_presupuesto' => $numero]);

            return Pedido::create([
                'empresa_id' => $usuario->empresa_id,
                'vendedor_id' => $usuario->id,
                'cliente_nombre' => Pedido::CLIENTE_POR_DEFECTO,
                'estado' => Pedido::ESTADO_PRESUPUESTO,
                'numero_presupuesto' => $numero,
            ]);
        });
    }
}
