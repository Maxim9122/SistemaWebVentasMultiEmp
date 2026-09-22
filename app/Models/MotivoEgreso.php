<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MotivoEgreso extends Model
{
    protected $table = 'motivos_egreso';

    // Se cargan por empresa la primera vez que hace falta (ver
    // Empresa::asegurarMotivosEgresoPorDefecto()) — el admin puede después
    // renombrarlos, agregar otros, desactivarlos o cambiarles los flags,
    // nunca son fijos en código más allá de este punto de partida.
    public const DEFAULTS = [
        ['nombre' => 'Pago proveedores', 'solo_proveedores' => true],
        ['nombre' => 'Consumos', 'requiere_producto' => true],
        ['nombre' => 'Retiros'],
    ];

    protected $fillable = [
        'empresa_id',
        'nombre',
        'activo',
        'requiere_producto',
        'solo_proveedores',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'requiere_producto' => 'boolean',
            'solo_proveedores' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
