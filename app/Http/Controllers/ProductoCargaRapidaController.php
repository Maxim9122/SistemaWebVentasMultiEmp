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
        $tipo = $request->validated('tipo');
        $accionPedido = $request->validated('accion_pedido');
        $clienteNombre = trim((string) ($request->validated('cliente_nombre') ?? ''));
        $puedeCambiarPrecio = $usuario->puedeCambiarPrecioVenta();

        $pedidoEnCurso = $tipo === 'presupuesto' ? $this->presupuestoEnCurso($usuario) : $this->carritoAbierto($usuario);

        // Si ya hay un carrito/presupuesto en curso y todavía no se sabe
        // qué prefiere el usuario, se pregunta en vez de decidir solo —
        // nada se agrega todavía en este caso, así que no importa si el
        // usuario termina eligiendo "crear nuevo".
        if ($pedidoEnCurso && $accionPedido === null) {
            return response()->json([
                'necesitaEleccion' => true,
                'etiquetaPedidoEnCurso' => $tipo === 'presupuesto'
                    ? "el presupuesto #{$pedidoEnCurso->numero_presupuesto} que ya tenías en curso"
                    : 'el carrito que ya tenías abierto',
            ]);
        }

        $pedido = $accionPedido === 'crear_nuevo'
            ? ($tipo === 'presupuesto' ? $this->crearPresupuesto($usuario, $clienteNombre) : $this->crearCarrito($usuario, $clienteNombre))
            : ($pedidoEnCurso ?? ($tipo === 'presupuesto' ? $this->crearPresupuesto($usuario, $clienteNombre) : $this->crearCarrito($usuario, $clienteNombre)));

        // Si se reutiliza un pedido que ya existía, el nombre recién llega
        // ahora (crearCarrito()/crearPresupuesto() ya no corren en este
        // caso) — se pisa el cliente_id también: un nombre a mano no
        // corresponde necesariamente al cliente que tenía vinculado antes.
        if ($clienteNombre !== '' && $pedido->cliente_nombre !== $clienteNombre) {
            $pedido->update(['cliente_nombre' => $clienteNombre, 'cliente_id' => null]);
        }

        $agregados = 0;

        // Todos los productos pedidos van al MISMO $pedido (ver arriba) —
        // acá adentro solo se repite, por cada uno, la misma lógica de
        // fusionar con una línea ya cargada o crear una nueva.
        foreach ($request->validated('productos') as $itemSolicitado) {
            $producto = Producto::where('empresa_id', $usuario->empresa_id)->find($itemSolicitado['producto_id']);

            if (! $producto) {
                continue;
            }

            $cantidad = $producto->esPorPeso() ? 1 : (int) $itemSolicitado['cantidad'];
            $precioSolicitado = $itemSolicitado['precio_unitario'] ?? null;

            // Mismo motivo que en CarritoController::agregarItem(): un
            // producto por peso nunca se fusiona con una línea ya cargada.
            $itemExistente = $producto->esPorPeso() ? null : $pedido->items()->where('producto_id', $producto->id)->first();

            if ($itemExistente) {
                $nuevaCantidad = $itemExistente->cantidad + $cantidad;
                $resolucion = $this->resolverPrecio(
                    $producto,
                    $nuevaCantidad,
                    $precioSolicitado,
                    $puedeCambiarPrecio,
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
                $resolucion = $this->resolverPrecio($producto, $cantidad, $precioSolicitado, $puedeCambiarPrecio, false, (float) $producto->precio, conservarManualSiNoSeEnviaPrecio: false);

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

            $agregados++;
        }

        $pedido->recalcularTotal();

        return response()->json([
            'mensaje' => $this->mensajeResultado($tipo, $agregados),
            'tipo' => $tipo,
            'pedido_id' => $pedido->id,
            'agregados' => $agregados,
        ]);
    }

    private function mensajeResultado(string $tipo, int $agregados): string
    {
        $destino = $tipo === 'presupuesto' ? 'al presupuesto' : 'al carrito';

        return $agregados === 1
            ? "Producto agregado {$destino}."
            : "{$agregados} productos agregados {$destino}.";
    }

    /**
     * @return array{precio: float, manual: bool}
     */
    private function resolverPrecio(
        Producto $producto,
        int $cantidad,
        ?string $precioSolicitado,
        bool $puedeCambiar,
        bool $eraManual,
        float $precioManualPrevio,
        bool $conservarManualSiNoSeEnviaPrecio,
    ): array {
        if ($producto->esPorPeso()) {
            return ['precio' => (float) $precioSolicitado, 'manual' => true];
        }

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

    private function crearCarrito(User $usuario, string $clienteNombre = ''): Pedido
    {
        return Pedido::create([
            'empresa_id' => $usuario->empresa_id,
            'vendedor_id' => $usuario->id,
            'cliente_nombre' => $clienteNombre !== '' ? $clienteNombre : Pedido::CLIENTE_POR_DEFECTO,
            'estado' => Pedido::ESTADO_CARRITO,
        ]);
    }

    /**
     * Mismo criterio que carritoAbierto(), con un límite extra: solo se
     * reutiliza un presupuesto creado HOY. Sin ese límite, un presupuesto
     * de ayer que nadie cerró se seguía reutilizando sin fecha de corte —
     * y como la lista de Presupuestos solo muestra los de hoy por defecto
     * (ver PresupuestoController::index()), los productos se iban cargando
     * en un presupuesto que después no aparecía en ningún lado sin cambiar
     * el filtro de fecha a mano.
     */
    private function presupuestoEnCurso(User $usuario): ?Pedido
    {
        return Pedido::query()
            ->where('vendedor_id', $usuario->id)
            ->where('estado', Pedido::ESTADO_PRESUPUESTO)
            ->whereDate('created_at', today())
            ->latest()
            ->first();
    }

    private function crearPresupuesto(User $usuario, string $clienteNombre = ''): Pedido
    {
        return DB::transaction(function () use ($usuario, $clienteNombre) {
            $empresa = Empresa::where('id', $usuario->empresa_id)->lockForUpdate()->first();
            $numero = $empresa->ultimo_numero_presupuesto + 1;
            $empresa->update(['ultimo_numero_presupuesto' => $numero]);

            return Pedido::create([
                'empresa_id' => $usuario->empresa_id,
                'vendedor_id' => $usuario->id,
                'cliente_nombre' => $clienteNombre !== '' ? $clienteNombre : Pedido::CLIENTE_POR_DEFECTO,
                'estado' => Pedido::ESTADO_PRESUPUESTO,
                'numero_presupuesto' => $numero,
            ]);
        });
    }
}
