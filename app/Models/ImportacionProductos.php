<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportacionProductos extends Model
{
    use HasFactory;

    protected $table = 'importaciones_productos';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'proveedor_id',
        'nombre_archivo',
        'creados_count',
        'actualizados_count',
        'errores_count',
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

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function cambios(): HasMany
    {
        return $this->hasMany(ImportacionProductoCambio::class, 'importacion_id');
    }

    public function fueRevertida(): bool
    {
        return $this->revertida_at !== null;
    }
}
