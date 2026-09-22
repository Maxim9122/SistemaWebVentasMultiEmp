<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    use HasFactory;

    public const ESTADO_CARRITO = 'carrito';

    public const ESTADO_EN_CAJA = 'en_caja';

    public const ESTADO_PROGRAMADO = 'programado';

    public const ESTADO_CANCELADO = 'cancelado';

    public const ESTADO_COBRADO = 'cobrado';

    public const ESTADO_PRESUPUESTO = 'presupuesto';

    public const CLIENTE_POR_DEFECTO = 'Consumidor Final';

    public const FORMAS_PAGO = ['efectivo', 'tarjeta', 'transferencia'];

    public const FORMA_PAGO_MIXTO = 'mixto';

    public const TIPOS_COMPROBANTE = ['remito', 'factura'];

    public const TIPOS_FACTURA = ['A', 'B', 'C'];

    protected $fillable = [
        'empresa_id',
        'vendedor_id',
        'cliente_nombre',
        'estado',
        'fecha_programada',
        'notas',
        'total',
        'cobrado_por',
        'caja_id',
        'forma_pago',
        'tipo_comprobante',
        'cobrado_at',
        'factura_id',
        'numero_venta',
        'numero_presupuesto',
        'monto_efectivo',
        'monto_tarjeta',
        'monto_transferencia',
        'monto_fiado',
        'cliente_id',
        'ajuste_efectivo_porcentaje',
        'ajuste_tarjeta_porcentaje',
        'ajuste_transferencia_porcentaje',
        'total_cobrado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_programada' => 'date',
            'total' => 'decimal:2',
            'cobrado_at' => 'datetime',
            'monto_efectivo' => 'decimal:2',
            'monto_tarjeta' => 'decimal:2',
            'monto_transferencia' => 'decimal:2',
            'monto_fiado' => 'decimal:2',
            'ajuste_efectivo_porcentaje' => 'decimal:2',
            'ajuste_tarjeta_porcentaje' => 'decimal:2',
            'ajuste_transferencia_porcentaje' => 'decimal:2',
            'total_cobrado' => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function cajero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cobrado_por');
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    /**
     * Todas las facturas que salieron de este pedido a lo largo del tiempo
     * (no solo la vigente vía `factura_id`) — al editar una venta ya
     * facturada, `factura_id` pasa a apuntar a la factura nueva y la vieja
     * queda histórica acá, con su nota de crédito asociada.
     */
    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class);
    }

    public function modificaciones(): HasMany
    {
        return $this->hasMany(VentaModificacion::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PedidoItem::class);
    }

    public function esCarrito(): bool
    {
        return $this->estado === self::ESTADO_CARRITO;
    }

    public function esEnCaja(): bool
    {
        return $this->estado === self::ESTADO_EN_CAJA;
    }

    public function esProgramado(): bool
    {
        return $this->estado === self::ESTADO_PROGRAMADO;
    }

    public function esCobrado(): bool
    {
        return $this->estado === self::ESTADO_COBRADO;
    }

    public function esPresupuesto(): bool
    {
        return $this->estado === self::ESTADO_PRESUPUESTO;
    }

    public function recalcularTotal(): void
    {
        $this->update(['total' => $this->items()->sum('subtotal')]);
    }

    public function esFiado(): bool
    {
        return (float) $this->monto_fiado > 0;
    }
}
