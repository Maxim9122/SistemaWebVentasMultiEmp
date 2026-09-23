<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReposicionStock extends Model
{
    protected $table = 'reposiciones_stock';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'proveedor_id',
        'nota',
        'revertida_at',
    ];

    protected function casts(): array
    {
        return [
            'revertida_at' => 'datetime',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReposicionStockItem::class, 'reposicion_id');
    }

    public function fueRevertida(): bool
    {
        return $this->revertida_at !== null;
    }
}
