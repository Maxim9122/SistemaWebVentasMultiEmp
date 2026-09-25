<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AjustePrecioBusquedaCambio extends Model
{
    protected $fillable = [
        'ajuste_id',
        'producto_id',
        'precio_anterior',
        'precio_nuevo',
    ];

    protected function casts(): array
    {
        return [
            'precio_anterior' => 'decimal:2',
            'precio_nuevo' => 'decimal:2',
        ];
    }

    public function ajuste(): BelongsTo
    {
        return $this->belongsTo(AjustePrecioBusqueda::class, 'ajuste_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
