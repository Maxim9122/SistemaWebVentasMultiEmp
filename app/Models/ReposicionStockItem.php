<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReposicionStockItem extends Model
{
    protected $fillable = [
        'reposicion_id',
        'producto_id',
        'nombre_producto',
        'cantidad_anterior',
        'cantidad_agregada',
        'cantidad_nueva',
    ];

    public function reposicion(): BelongsTo
    {
        return $this->belongsTo(ReposicionStock::class, 'reposicion_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
