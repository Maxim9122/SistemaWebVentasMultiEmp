<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AjustePrecioBusqueda extends Model
{
    protected $table = 'ajustes_precio_busqueda';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'porcentaje',
        'descripcion_filtro',
        'productos_count',
        'revertido_at',
    ];

    protected function casts(): array
    {
        return [
            'porcentaje' => 'decimal:2',
            'revertido_at' => 'datetime',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cambios(): HasMany
    {
        return $this->hasMany(AjustePrecioBusquedaCambio::class, 'ajuste_id');
    }

    public function fueRevertido(): bool
    {
        return $this->revertido_at !== null;
    }
}
