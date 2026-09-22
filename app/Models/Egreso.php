<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Egreso extends Model
{
    protected $fillable = [
        'empresa_id',
        'caja_id',
        'user_id',
        'motivo_egreso_id',
        'producto_id',
        'cantidad',
        'precio_unitario_aplicado',
        'proveedor_id',
        'beneficiario_user_id',
        'beneficiario_nombre',
        'monto_efectivo',
        'monto_transferencia',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'monto_efectivo' => 'decimal:2',
            'monto_transferencia' => 'decimal:2',
            'precio_unitario_aplicado' => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function motivo(): BelongsTo
    {
        return $this->belongsTo(MotivoEgreso::class, 'motivo_egreso_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function beneficiarioUsuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'beneficiario_user_id');
    }

    public function total(): float
    {
        return round((float) $this->monto_efectivo + (float) $this->monto_transferencia, 2);
    }

    /**
     * Nombre a mostrar del beneficiario, sea cual sea la fuente (proveedor
     * registrado, staff registrado, o texto libre) — como mucho una está
     * cargada.
     */
    public function beneficiarioLegible(): ?string
    {
        return $this->proveedor?->nombre ?? $this->beneficiarioUsuario?->name ?? $this->beneficiario_nombre;
    }
}
