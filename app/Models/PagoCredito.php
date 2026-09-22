<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoCredito extends Model
{
    protected $table = 'pagos_credito';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'user_id',
        'caja_id',
        'monto_efectivo',
        'monto_tarjeta',
        'monto_transferencia',
    ];

    protected function casts(): array
    {
        return [
            'monto_efectivo' => 'decimal:2',
            'monto_tarjeta' => 'decimal:2',
            'monto_transferencia' => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function total(): float
    {
        return round((float) $this->monto_efectivo + (float) $this->monto_tarjeta + (float) $this->monto_transferencia, 2);
    }
}
