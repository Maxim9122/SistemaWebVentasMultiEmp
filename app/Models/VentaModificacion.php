<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaModificacion extends Model
{
    use HasFactory;

    protected $table = 'venta_modificaciones';

    protected $fillable = [
        'pedido_id',
        'user_id',
        'motivo',
        'total_anterior',
        'total_nuevo',
        'items_anteriores',
        'items_nuevos',
    ];

    protected function casts(): array
    {
        return [
            'total_anterior' => 'decimal:2',
            'total_nuevo' => 'decimal:2',
            'items_anteriores' => 'array',
            'items_nuevos' => 'array',
        ];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
