<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Factura;
use App\Models\NotaCredito;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Producto;
use App\Models\User;
use App\Models\VentaModificacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProcesadorDeCobro
{
    /**
     * Cobra un pedido: valida que los montos por medio de pago cubran
     * exactamente el total, descuenta stock (bloqueando las filas
     * involucradas para que dos cobros concurrentes del mismo producto no
     * se pisen), aplica el % de ajuste de cada medio de pago, asigna el
     * número de venta correlativo de la empresa, registra la factura si
     * corresponde, y marca el pedido como cobrado. Tira \RuntimeException
     * si el pedido ya fue procesado, si los montos no cierran contra el
     * total, o si no alcanza el stock — en cualquiera de esos casos no se
     * modifica nada.
     *
     * @param  array<string, float|null>  $montos  ['efectivo' => .., 'tarjeta' => .., 'transferencia' => ..]
     * @param  array{nombre: string, cuit: string, telefono?: string|null}|null  $clienteNuevo
     * @param  float  $montoFiado  Parte del total que queda a crédito (no se cobra ahora). No es un
     *                             medio de pago más: no tiene ajuste, no suma a total_cobrado (no es
     *                             plata que entró a la caja), y exige un cliente identificado.
     * @return Factura|null  La factura recién creada (si tipoComprobante='factura'), para que el caller
     *                       dispare la emisión del comprobante DESPUÉS de esta transacción (sin locks activos).
     */
    public function cobrar(
        Pedido $pedido,
        User $cajero,
        array $montos,
        string $tipoComprobante,
        ?int $clienteId = null,
        ?array $clienteNuevo = null,
        ?string $tipoFactura = null,
        float $montoFiado = 0.0,
    ): ?Factura {
        return DB::transaction(function () use ($pedido, $cajero, $montos, $tipoComprobante, $clienteId, $clienteNuevo, $tipoFactura, $montoFiado) {
            $pedidoActual = Pedido::where('id', $pedido->id)->lockForUpdate()->first();

            if (in_array($pedidoActual->estado, [Pedido::ESTADO_COBRADO, Pedido::ESTADO_CANCELADO], true)) {
                throw new \RuntimeException('Este pedido ya fue procesado.');
            }

            $caja = $cajero->cajaAbierta();

            if (! $caja) {
                throw new \RuntimeException('Tenés que abrir tu caja antes de cobrar.');
            }

            $empresa = $pedidoActual->empresa;

            if ($montoFiado > 0 && ! $empresa->permite_fiado) {
                throw new \RuntimeException('Esta empresa no tiene habilitada la venta a crédito (fiado).');
            }

            $suma = round(array_sum(array_map(fn ($m) => (float) ($m ?? 0), $montos)) + $montoFiado, 2);

            if (bccomp((string) $suma, (string) $pedidoActual->total, 2) !== 0) {
                throw new \RuntimeException('Los montos ingresados no cubren exactamente el total del pedido.');
            }

            if ($empresa->controla_stock) {
                $this->descontarStock($pedidoActual);
            }

            $empresaLockeada = Empresa::where('id', $pedidoActual->empresa_id)->lockForUpdate()->first();
            $numeroVenta = $empresaLockeada->ultimo_numero_venta + 1;
            $empresaLockeada->update(['ultimo_numero_venta' => $numeroVenta]);

            $totalCobrado = 0.0;
            $mediosUsados = [];
            $datosPago = [];

            foreach (Pedido::FORMAS_PAGO as $medio) {
                $monto = (float) ($montos[$medio] ?? 0);

                if ($monto <= 0) {
                    $datosPago["monto_{$medio}"] = null;
                    $datosPago["ajuste_{$medio}_porcentaje"] = null;

                    continue;
                }

                $ajuste = $empresa->ajustePorcentajePara($medio);

                $datosPago["monto_{$medio}"] = $monto;
                $datosPago["ajuste_{$medio}_porcentaje"] = $ajuste;

                $totalCobrado += round($monto * (1 + $ajuste / 100), 2);
                $mediosUsados[] = $medio;
            }

            if ($montoFiado > 0) {
                $mediosUsados[] = 'fiado';
            }

            $formaPago = count($mediosUsados) > 1 ? Pedido::FORMA_PAGO_MIXTO : ($mediosUsados[0] ?? null);

            $clienteIdFinal = $clienteId;

            if (! $clienteIdFinal && $clienteNuevo) {
                $clienteIdFinal = Cliente::firstOrCreate(
                    ['empresa_id' => $pedidoActual->empresa_id, 'cuit' => $clienteNuevo['cuit']],
                    ['nombre' => $clienteNuevo['nombre'], 'telefono' => $clienteNuevo['telefono'] ?? null],
                )->id;
            }

            if ($montoFiado > 0 && ! $clienteIdFinal) {
                throw new \RuntimeException('Para fiar una venta hace falta elegir o cargar un cliente.');
            }

            $cliente = $clienteIdFinal ? Cliente::find($clienteIdFinal) : null;

            $factura = null;

            if ($tipoComprobante === 'factura') {
                $factura = Factura::create([
                    'empresa_id' => $pedidoActual->empresa_id,
                    'pedido_id' => $pedidoActual->id,
                    'cliente_id' => $clienteIdFinal,
                    'cliente_nombre' => $cliente->nombre ?? Pedido::CLIENTE_POR_DEFECTO,
                    'cliente_cuit' => $cliente->cuit ?? null,
                    'tipo_factura' => $tipoFactura,
                ]);
            }

            $pedidoActual->update([
                ...$datosPago,
                'estado' => Pedido::ESTADO_COBRADO,
                'forma_pago' => $formaPago,
                'tipo_comprobante' => $tipoComprobante,
                'factura_id' => $factura?->id,
                'cliente_id' => $clienteIdFinal,
                // Si se eligió/cargó un cliente real (típico en fiado o
                // factura), el ticket tiene que mostrar su nombre — antes
                // quedaba el "Consumidor Final" del carrito porque acá nunca
                // se actualizaba, solo se guardaba en la Factura.
                'cliente_nombre' => $cliente->nombre ?? $pedidoActual->cliente_nombre,
                'monto_fiado' => $montoFiado > 0 ? $montoFiado : null,
                'total_cobrado' => round($totalCobrado, 2),
                'cobrado_por' => $cajero->id,
                'caja_id' => $caja->id,
                'cobrado_at' => now(),
                'numero_venta' => $numeroVenta,
            ]);

            return $factura;
        });
    }

    /**
     * Anula el 100% de una venta ya facturada (con CAE): devuelve el stock
     * de todo lo vendido (si la empresa controla stock, misma expansión de
     * promociones que usa el resto del flujo) y crea la NotaCredito local
     * en estado pendiente, por el total facturado. No llama a la API de
     * facturación — eso lo dispara el caller después, fuera de esta
     * transacción, igual que EmisionComprobanteService tras cobrar(). Tira
     * \RuntimeException si la venta no está cobrada, no tiene factura
     * aprobada, o ya tiene una nota de crédito (no se puede anular dos
     * veces — alcance actual es 100% una sola vez).
     */
    public function anularVenta(Pedido $pedido, User $usuario, string $motivo): NotaCredito
    {
        return DB::transaction(function () use ($pedido, $usuario, $motivo) {
            $pedidoActual = Pedido::where('id', $pedido->id)->lockForUpdate()->first();

            if (! $pedidoActual->esCobrado()) {
                throw new \RuntimeException('Solo se pueden anular ventas cobradas.');
            }

            $factura = $pedidoActual->factura;

            if (! $factura || ! $factura->estaAprobada()) {
                throw new \RuntimeException('Solo se puede anular una venta con factura aprobada (con CAE).');
            }

            if ($factura->notaCredito) {
                throw new \RuntimeException('Esta factura ya tiene una nota de crédito.');
            }

            $empresa = $pedidoActual->empresa;

            if ($empresa->controla_stock) {
                foreach ($this->necesidadesStock($pedidoActual->items) as $productoId => $cantidad) {
                    Producto::where('id', $productoId)->lockForUpdate()->increment('stock', $cantidad);
                }
            }

            return NotaCredito::create([
                'empresa_id' => $pedidoActual->empresa_id,
                'factura_id' => $factura->id,
                'creado_por' => $usuario->id,
                // Se acredita el total facturado (lo que la factura
                // original informó a AFIP, ver EmisionComprobanteService),
                // no total_cobrado — ese excluye a propósito la parte fiada
                // y dejaría una venta fiada facturada sin acreditar bien.
                'importe_acreditado' => $pedidoActual->total,
                'motivo' => $motivo,
            ]);
        });
    }

    /**
     * Cotiza cada línea propuesta contra el catálogo (nombre, precio vía
     * Producto::precioParaCantidad() según la cantidad pedida, subtotal). No
     * persiste nada ni bloquea filas — sirve para previsualizar el total
     * antes de decidir si hace falta reabrir el cobro.
     *
     * Si una línea propuesta trae `precio_unitario` (no null), ese precio
     * manual gana siempre — es quien llama (el controller) el que decide si
     * corresponde aceptarlo según `User::puedeCambiarPrecioVenta()`, acá se
     * aplica tal cual sin volver a chequear permisos.
     *
     * Si no trae uno y se pasan $itemsActuales (los ítems que la venta tenía
     * antes de editar), una línea propuesta cuyo producto_id y cantidad
     * coincidan exactamente con una línea actual que tenía precio_manual=true
     * conserva ese precio especial tal cual, en vez de recalcularlo — así una
     * edición que solo toca una línea no pisa silenciosamente los precios
     * especiales de las líneas que no se tocaron. Las líneas nuevas o con
     * cantidad distinta siempre se recotizan automático.
     *
     * @param  list<array{producto_id: int, cantidad: int, precio_unitario?: float|null}>  $itemsPropuestos
     * @param  Collection<int, PedidoItem>|null  $itemsActuales
     * @return array{items: list<array{producto_id: int, nombre_producto: string, cantidad: int, precio_original: float, precio_unitario: float, precio_manual: bool, subtotal: float}>, total: float}
     */
    public function resolverItems(array $itemsPropuestos, ?Collection $itemsActuales = null): array
    {
        $actualesPorClave = ($itemsActuales ?? collect())
            ->filter(fn (PedidoItem $item) => $item->precio_manual)
            ->keyBy(fn (PedidoItem $item) => $item->producto_id.':'.$item->cantidad);

        $items = [];
        $total = 0.0;

        foreach ($itemsPropuestos as $propuesta) {
            $producto = Producto::find($propuesta['producto_id']);

            if (! $producto) {
                throw new \RuntimeException("No se encontró el producto #{$propuesta['producto_id']}.");
            }

            $cantidad = $propuesta['cantidad'];
            $precioManualSolicitado = $propuesta['precio_unitario'] ?? null;
            $clave = $producto->id.':'.$cantidad;
            $itemManualPrevio = $actualesPorClave->get($clave);

            if ($precioManualSolicitado !== null) {
                $precio = (float) $precioManualSolicitado;
                $manual = true;
            } elseif ($itemManualPrevio) {
                $precio = (float) $itemManualPrevio->precio_unitario;
                $manual = true;
            } else {
                $precio = $producto->precioParaCantidad($cantidad)['precio'];
                $manual = false;
            }

            $subtotal = round($cantidad * $precio, 2);

            $items[] = [
                'producto_id' => $producto->id,
                'nombre_producto' => $producto->nombre,
                'cantidad' => $cantidad,
                'precio_original' => (float) $producto->precio,
                'precio_unitario' => $precio,
                'precio_manual' => $manual,
                'subtotal' => $subtotal,
            ];

            $total += $subtotal;
        }

        return ['items' => $items, 'total' => round($total, 2)];
    }

    /**
     * Reemplaza los ítems de una venta ya cobrada. Ajusta stock por el delta
     * entre lo que había y lo nuevo (si la empresa controla stock), recalcula
     * el total, y:
     *  - si el total nuevo coincide con total_cobrado: no toca montos/ajustes.
     *  - si no coincide: exige que $montosNuevos cierre exacto contra el total
     *    nuevo (mismo criterio que cobrar()) y recalcula
     *    monto_X/ajuste_X/total_cobrado/forma_pago igual que cobrar().
     * Registra todo en venta_modificaciones con snapshot antes/después. Tira
     * \RuntimeException si el pedido no está cobrado, si no hay stock
     * suficiente para el delta, o si los montos no cierran — en cualquiera de
     * esos casos no se modifica nada.
     *
     * @param  list<array{producto_id: int, cantidad: int}>  $itemsPropuestos
     * @param  array<string, float|null>|null  $montosNuevos  Requerido solo si el total cambia.
     */
    public function editarItems(
        Pedido $pedido,
        User $usuario,
        array $itemsPropuestos,
        ?string $motivo,
        ?array $montosNuevos = null,
    ): void {
        DB::transaction(function () use ($pedido, $usuario, $itemsPropuestos, $motivo, $montosNuevos) {
            $pedidoActual = Pedido::where('id', $pedido->id)->lockForUpdate()->first();

            if ($pedidoActual->estado !== Pedido::ESTADO_COBRADO) {
                throw new \RuntimeException('Solo se pueden editar ventas cobradas.');
            }

            $itemsAnteriores = $pedidoActual->items()->get();
            $resuelto = $this->resolverItems($itemsPropuestos, $itemsAnteriores);
            $itemsNuevos = $resuelto['items'];
            $totalNuevo = $resuelto['total'];

            $empresa = $pedidoActual->empresa;

            if ($empresa->controla_stock) {
                $necesitadosAntes = $this->necesidadesStock($itemsAnteriores);
                $necesitadosDespues = $this->necesidadesStock($itemsNuevos);

                $productoIds = array_unique(array_merge(array_keys($necesitadosAntes), array_keys($necesitadosDespues)));
                sort($productoIds);

                foreach ($productoIds as $productoId) {
                    $delta = ($necesitadosDespues[$productoId] ?? 0) - ($necesitadosAntes[$productoId] ?? 0);

                    if ($delta === 0) {
                        continue;
                    }

                    $producto = Producto::where('id', $productoId)->lockForUpdate()->first();

                    if (! $producto) {
                        throw new \RuntimeException("No se encontró el producto #{$productoId}.");
                    }

                    if ($delta > 0) {
                        if ($producto->stock < $delta) {
                            throw new \RuntimeException("No hay stock suficiente de \"{$producto->nombre}\" para este cambio.");
                        }

                        $producto->decrement('stock', $delta);
                    } else {
                        $producto->increment('stock', abs($delta));
                    }
                }
            }

            $datosActualizacion = ['total' => $totalNuevo];

            if (bccomp((string) $totalNuevo, (string) $pedidoActual->total_cobrado, 2) !== 0) {
                if ($montosNuevos === null) {
                    throw new \RuntimeException('El total cambió: hay que indicar cómo se cobra la diferencia.');
                }

                $suma = round(array_sum(array_map(fn ($m) => (float) ($m ?? 0), $montosNuevos)), 2);

                if (bccomp((string) $suma, (string) $totalNuevo, 2) !== 0) {
                    throw new \RuntimeException('Los montos ingresados no cubren exactamente el nuevo total.');
                }

                $totalCobrado = 0.0;
                $mediosUsados = [];

                foreach (Pedido::FORMAS_PAGO as $medio) {
                    $monto = (float) ($montosNuevos[$medio] ?? 0);

                    if ($monto <= 0) {
                        $datosActualizacion["monto_{$medio}"] = null;
                        $datosActualizacion["ajuste_{$medio}_porcentaje"] = null;

                        continue;
                    }

                    $ajuste = $empresa->ajustePorcentajePara($medio);
                    $datosActualizacion["monto_{$medio}"] = $monto;
                    $datosActualizacion["ajuste_{$medio}_porcentaje"] = $ajuste;
                    $totalCobrado += round($monto * (1 + $ajuste / 100), 2);
                    $mediosUsados[] = $medio;
                }

                $datosActualizacion['forma_pago'] = count($mediosUsados) > 1
                    ? Pedido::FORMA_PAGO_MIXTO
                    : ($mediosUsados[0] ?? null);
                $datosActualizacion['total_cobrado'] = round($totalCobrado, 2);
            }

            $snapshot = fn ($items) => collect($items)->map(fn ($i) => is_array($i) ? [
                'producto_id' => $i['producto_id'],
                'nombre_producto' => $i['nombre_producto'],
                'cantidad' => $i['cantidad'],
                'precio_unitario' => $i['precio_unitario'],
                'subtotal' => $i['subtotal'],
            ] : [
                'producto_id' => $i->producto_id,
                'nombre_producto' => $i->nombre_producto,
                'cantidad' => $i->cantidad,
                'precio_unitario' => (float) $i->precio_unitario,
                'subtotal' => (float) $i->subtotal,
            ])->all();

            $itemsAnterioresSnapshot = $snapshot($itemsAnteriores);
            $totalAnterior = (float) $pedidoActual->total;

            $pedidoActual->items()->delete();

            foreach ($itemsNuevos as $item) {
                PedidoItem::create([
                    'pedido_id' => $pedidoActual->id,
                    'producto_id' => $item['producto_id'],
                    'nombre_producto' => $item['nombre_producto'],
                    'cantidad' => $item['cantidad'],
                    'precio_original' => $item['precio_original'],
                    'precio_unitario' => $item['precio_unitario'],
                    'precio_manual' => $item['precio_manual'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            $pedidoActual->update($datosActualizacion);

            VentaModificacion::create([
                'pedido_id' => $pedidoActual->id,
                'user_id' => $usuario->id,
                'motivo' => $motivo,
                'total_anterior' => $totalAnterior,
                'total_nuevo' => $totalNuevo,
                'items_anteriores' => $itemsAnterioresSnapshot,
                'items_nuevos' => $snapshot($itemsNuevos),
            ]);
        });
    }

    /**
     * Como editarItems(), pero para una venta que ya tiene una factura
     * APROBADA (con CAE): AFIP no permite corregir un comprobante ya
     * emitido, la única forma válida es anularlo (nota de crédito por el
     * 100% del importe original) y facturar de nuevo con los datos
     * actualizados — nunca "corrige en el lugar" la factura existente. La
     * factura nueva copia cliente y tipo de comprobante de la original (acá
     * solo se editan ítems, no a quién ni cómo se factura).
     *
     * "Factura válida para reemplazar" se verifica con estaAprobada() —
     * estado aprobada Y con CAE. Si la factura ya tiene una nota de crédito
     * (por ejemplo porque el admin ya la anuló a mano con "Anular factura"
     * antes de decidir editar), **no se emite una segunda** — sería
     * acreditar el mismo importe dos veces. En ese caso solo se genera la
     * factura nueva con los datos corregidos, y el resultado devuelve
     * `notaCredito: null` para que el caller sepa que no hay nada que
     * emitir por ese lado.
     *
     * Ninguna de las dos (si corresponde NC) se emite ante la API de
     * facturación acá — eso lo dispara el caller después de esta
     * transacción, mismo patrón que el resto del flujo. Tira
     * \RuntimeException si el pedido no está cobrado, si no tiene una
     * factura aprobada (con CAE) para reemplazar, si no hay stock
     * suficiente para el delta, o si los montos no cierran — en cualquiera
     * de esos casos no se modifica nada.
     *
     * @param  list<array{producto_id: int, cantidad: int}>  $itemsPropuestos
     * @param  array<string, float|null>|null  $montosNuevos  Requerido solo si el total cambia.
     * @return array{notaCredito: ?NotaCredito, facturaNueva: Factura}
     */
    public function editarVentaFacturada(
        Pedido $pedido,
        User $usuario,
        array $itemsPropuestos,
        ?string $motivo,
        ?array $montosNuevos = null,
    ): array {
        return DB::transaction(function () use ($pedido, $usuario, $itemsPropuestos, $motivo, $montosNuevos) {
            $pedidoActual = Pedido::where('id', $pedido->id)->lockForUpdate()->first();

            if ($pedidoActual->estado !== Pedido::ESTADO_COBRADO) {
                throw new \RuntimeException('Solo se pueden editar ventas cobradas.');
            }

            $facturaOriginal = $pedidoActual->factura;

            if (! $facturaOriginal || ! $facturaOriginal->estaAprobada()) {
                throw new \RuntimeException('Esta venta no tiene una factura aprobada (con CAE) para reemplazar.');
            }

            $yaTieneNotaCredito = $facturaOriginal->notaCredito !== null;

            $itemsAnteriores = $pedidoActual->items()->get();
            $resuelto = $this->resolverItems($itemsPropuestos, $itemsAnteriores);
            $itemsNuevos = $resuelto['items'];
            $totalNuevo = $resuelto['total'];

            $empresa = $pedidoActual->empresa;

            if ($empresa->controla_stock) {
                $necesitadosAntes = $this->necesidadesStock($itemsAnteriores);
                $necesitadosDespues = $this->necesidadesStock($itemsNuevos);

                $productoIds = array_unique(array_merge(array_keys($necesitadosAntes), array_keys($necesitadosDespues)));
                sort($productoIds);

                foreach ($productoIds as $productoId) {
                    $delta = ($necesitadosDespues[$productoId] ?? 0) - ($necesitadosAntes[$productoId] ?? 0);

                    if ($delta === 0) {
                        continue;
                    }

                    $producto = Producto::where('id', $productoId)->lockForUpdate()->first();

                    if (! $producto) {
                        throw new \RuntimeException("No se encontró el producto #{$productoId}.");
                    }

                    if ($delta > 0) {
                        if ($producto->stock < $delta) {
                            throw new \RuntimeException("No hay stock suficiente de \"{$producto->nombre}\" para este cambio.");
                        }

                        $producto->decrement('stock', $delta);
                    } else {
                        $producto->increment('stock', abs($delta));
                    }
                }
            }

            $notaCredito = $yaTieneNotaCredito ? null : NotaCredito::create([
                'empresa_id' => $pedidoActual->empresa_id,
                'factura_id' => $facturaOriginal->id,
                'creado_por' => $usuario->id,
                // Se acredita el total facturado (lo que la factura
                // original informó a AFIP, ver EmisionComprobanteService),
                // no total_cobrado — ese excluye a propósito la parte fiada
                // y dejaría una venta fiada facturada sin acreditar bien.
                'importe_acreditado' => $pedidoActual->total,
                'motivo' => $motivo ?: 'Corrección de la venta: se reemplaza por una factura nueva con los datos actualizados.',
            ]);

            $datosActualizacion = ['total' => $totalNuevo];

            if (bccomp((string) $totalNuevo, (string) $pedidoActual->total_cobrado, 2) !== 0) {
                if ($montosNuevos === null) {
                    throw new \RuntimeException('El total cambió: hay que indicar cómo se cobra la diferencia.');
                }

                $suma = round(array_sum(array_map(fn ($m) => (float) ($m ?? 0), $montosNuevos)), 2);

                if (bccomp((string) $suma, (string) $totalNuevo, 2) !== 0) {
                    throw new \RuntimeException('Los montos ingresados no cubren exactamente el nuevo total.');
                }

                $totalCobrado = 0.0;
                $mediosUsados = [];

                foreach (Pedido::FORMAS_PAGO as $medio) {
                    $monto = (float) ($montosNuevos[$medio] ?? 0);

                    if ($monto <= 0) {
                        $datosActualizacion["monto_{$medio}"] = null;
                        $datosActualizacion["ajuste_{$medio}_porcentaje"] = null;

                        continue;
                    }

                    $ajuste = $empresa->ajustePorcentajePara($medio);
                    $datosActualizacion["monto_{$medio}"] = $monto;
                    $datosActualizacion["ajuste_{$medio}_porcentaje"] = $ajuste;
                    $totalCobrado += round($monto * (1 + $ajuste / 100), 2);
                    $mediosUsados[] = $medio;
                }

                $datosActualizacion['forma_pago'] = count($mediosUsados) > 1
                    ? Pedido::FORMA_PAGO_MIXTO
                    : ($mediosUsados[0] ?? null);
                $datosActualizacion['total_cobrado'] = round($totalCobrado, 2);
            }

            $snapshot = fn ($items) => collect($items)->map(fn ($i) => is_array($i) ? [
                'producto_id' => $i['producto_id'],
                'nombre_producto' => $i['nombre_producto'],
                'cantidad' => $i['cantidad'],
                'precio_unitario' => $i['precio_unitario'],
                'subtotal' => $i['subtotal'],
            ] : [
                'producto_id' => $i->producto_id,
                'nombre_producto' => $i->nombre_producto,
                'cantidad' => $i->cantidad,
                'precio_unitario' => (float) $i->precio_unitario,
                'subtotal' => (float) $i->subtotal,
            ])->all();

            $itemsAnterioresSnapshot = $snapshot($itemsAnteriores);
            $totalAnterior = (float) $pedidoActual->total;

            $pedidoActual->items()->delete();

            foreach ($itemsNuevos as $item) {
                PedidoItem::create([
                    'pedido_id' => $pedidoActual->id,
                    'producto_id' => $item['producto_id'],
                    'nombre_producto' => $item['nombre_producto'],
                    'cantidad' => $item['cantidad'],
                    'precio_original' => $item['precio_original'],
                    'precio_unitario' => $item['precio_unitario'],
                    'precio_manual' => $item['precio_manual'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            $facturaNueva = Factura::create([
                'empresa_id' => $pedidoActual->empresa_id,
                'pedido_id' => $pedidoActual->id,
                'cliente_id' => $facturaOriginal->cliente_id,
                'cliente_nombre' => $facturaOriginal->cliente_nombre,
                'cliente_cuit' => $facturaOriginal->cliente_cuit,
                'tipo_factura' => $facturaOriginal->tipo_factura,
            ]);

            $datosActualizacion['factura_id'] = $facturaNueva->id;

            $pedidoActual->update($datosActualizacion);

            VentaModificacion::create([
                'pedido_id' => $pedidoActual->id,
                'user_id' => $usuario->id,
                'motivo' => $motivo,
                'total_anterior' => $totalAnterior,
                'total_nuevo' => $totalNuevo,
                'items_anteriores' => $itemsAnterioresSnapshot,
                'items_nuevos' => $snapshot($itemsNuevos),
            ]);

            return ['notaCredito' => $notaCredito, 'facturaNueva' => $facturaNueva];
        });
    }

    /**
     * Reemplaza los ítems de un pedido que **todavía no se cobró**
     * (`en_caja` o `programado`) — un simple update local: no hay stock que
     * ajustar (nunca se descontó, eso recién pasa en `cobrar()`), no hay
     * factura ni remito que tocar (no se emitió nada todavía), y no queda
     * registro en `venta_modificaciones` (no es la corrección de una venta
     * ya hecha, es simplemente terminar de armar el pedido). Tira
     * \RuntimeException si el pedido ya está cobrado o cancelado.
     *
     * @param  list<array{producto_id: int, cantidad: int}>  $itemsPropuestos
     */
    public function editarItemsPreCobro(Pedido $pedido, array $itemsPropuestos): void
    {
        DB::transaction(function () use ($pedido, $itemsPropuestos) {
            $pedidoActual = Pedido::where('id', $pedido->id)->lockForUpdate()->first();

            if (! in_array($pedidoActual->estado, [Pedido::ESTADO_EN_CAJA, Pedido::ESTADO_PROGRAMADO], true)) {
                throw new \RuntimeException('Solo se pueden editar pedidos en caja o programados, todavía sin cobrar.');
            }

            $resuelto = $this->resolverItems($itemsPropuestos, $pedidoActual->items()->get());

            $pedidoActual->items()->delete();

            foreach ($resuelto['items'] as $item) {
                PedidoItem::create([
                    'pedido_id' => $pedidoActual->id,
                    'producto_id' => $item['producto_id'],
                    'nombre_producto' => $item['nombre_producto'],
                    'cantidad' => $item['cantidad'],
                    'precio_original' => $item['precio_original'],
                    'precio_unitario' => $item['precio_unitario'],
                    'precio_manual' => $item['precio_manual'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            $pedidoActual->update(['total' => $resuelto['total']]);
        });
    }

    /**
     * Cuántas unidades de cada producto real hacen falta para cubrir estos
     * ítems, expandiendo promociones a sus componentes. No bloquea filas ni
     * valida stock — solo calcula.
     *
     * @param  iterable<PedidoItem|array{producto_id: int, cantidad: int}>  $items
     * @return array<int, int>  [producto_id_real => cantidad_necesaria]
     */
    private function necesidadesStock(iterable $items): array
    {
        $necesarios = [];

        foreach ($items as $item) {
            $productoId = is_array($item) ? $item['producto_id'] : $item->producto_id;
            $cantidad = is_array($item) ? $item['cantidad'] : $item->cantidad;

            if ($productoId === null) {
                continue;
            }

            $producto = Producto::with('componentes')->find($productoId);

            if (! $producto) {
                throw new \RuntimeException("No se encontró el producto #{$productoId}.");
            }

            if ($producto->es_promocion) {
                foreach ($producto->componentes as $componente) {
                    $necesarios[$componente->producto_id] = ($necesarios[$componente->producto_id] ?? 0)
                        + $componente->cantidad * $cantidad;
                }
            } else {
                $necesarios[$producto->id] = ($necesarios[$producto->id] ?? 0) + $cantidad;
            }
        }

        return $necesarios;
    }

    /**
     * Valida y descuenta stock de todo lo vendido en el pedido. Las promociones
     * no tienen stock propio: se descuenta de los productos reales que las
     * componen, multiplicando la cantidad de la promo vendida por la cantidad
     * de cada componente dentro de la promo. Si el mismo producto real aparece
     * suelto y también dentro de una promo en el mismo pedido, la necesidad se
     * suma antes de validar, para no bloquear ni descontar dos veces la misma fila.
     */
    private function descontarStock(Pedido $pedidoActual): void
    {
        $this->descontarStockDeItems($pedidoActual->items);
    }

    /**
     * Misma lógica de `descontarStock()` (expansión de promociones, lock por
     * fila, validación todo-o-nada) pero sin depender de un Pedido — la usa
     * también el egreso de tipo "consumo interno", que descuenta stock de un
     * único producto sin pasar por una venta.
     *
     * @param  iterable<array{producto_id: int, cantidad: int}>  $items
     */
    public function descontarStockDeItems(iterable $items): void
    {
        foreach ($this->necesidadesStock($items) as $productoId => $cantidadNecesaria) {
            $producto = Producto::where('id', $productoId)->lockForUpdate()->first();

            if (! $producto || $producto->stock < $cantidadNecesaria) {
                $nombre = $producto->nombre ?? "producto #{$productoId}";

                throw new \RuntimeException("No hay stock suficiente de \"{$nombre}\".");
            }

            $producto->decrement('stock', $cantidadNecesaria);
        }
    }
}
