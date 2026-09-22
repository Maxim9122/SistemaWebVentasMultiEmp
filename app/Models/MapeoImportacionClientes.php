<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapeoImportacionClientes extends Model
{
    use HasFactory;

    protected $table = 'mapeos_importacion_clientes';

    protected $fillable = [
        'empresa_id',
        'mapeo',
    ];

    protected function casts(): array
    {
        return [
            'mapeo' => 'array',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
