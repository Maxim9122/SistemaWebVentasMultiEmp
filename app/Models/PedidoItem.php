<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'pedido_id',
        'producto_id',
        'nombre_producto',
        'cantidad',
        'precio_original',
        'precio_unitario',
        'precio_manual',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_original' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
            'precio_manual' => 'boolean',
            'subtotal' => 'decimal:2',
        ];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function tienePrecioEspecial(): bool
    {
        return $this->precio_manual;
    }

    public function tienePrecioPorCantidad(): bool
    {
        return ! $this->precio_manual
            && bccomp((string) $this->precio_unitario, (string) $this->precio_original, 2) !== 0;
    }
}
