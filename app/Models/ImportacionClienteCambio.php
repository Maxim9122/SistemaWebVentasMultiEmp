<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportacionClienteCambio extends Model
{
    use HasFactory;

    protected $table = 'importacion_cliente_cambios';

    protected $fillable = [
        'importacion_id',
        'cliente_id',
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
        return $this->belongsTo(ImportacionClientes::class, 'importacion_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
