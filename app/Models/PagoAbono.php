<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoAbono extends Model
{
    protected $table = 'pagos_abono';

    protected $fillable = [
        'empresa_id',
        'registrado_por_id',
        'fecha_pago',
        'monto',
    ];

    protected function casts(): array
    {
        return [
            'fecha_pago' => 'date',
            'monto' => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }
}
