<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportacionProductoCambio extends Model
{
    use HasFactory;

    protected $table = 'importacion_producto_cambios';

    protected $fillable = [
        'importacion_id',
        'producto_id',
        'fue_creado',
        'datos_anteriores',
    ];

    protected function casts(): array
    {
        return [
            'fue_creado' => 'boolean',
            'datos_anteriores' => 'array',
        ];
    }

    public function importacion(): BelongsTo
    {
        return $this->belongsTo(ImportacionProductos::class, 'importacion_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
