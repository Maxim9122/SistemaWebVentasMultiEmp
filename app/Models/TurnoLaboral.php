<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TurnoLaboral extends Model
{
    protected $table = 'turnos_laborales';

    protected $fillable = [
        'empresa_id',
        'dia_semana',
        'hora_desde',
        'hora_hasta',
        'limite_cajeros',
    ];

    public const DIAS = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function nombreDia(): string
    {
        return self::DIAS[$this->dia_semana] ?? '—';
    }

    /**
     * hora_desde/hora_hasta llegan de MySQL como "HH:MM:SS" — se comparan como
     * texto (funciona por el padding fijo) en vez de parsear con Carbon, que
     * para columnas TIME puras trae más complicaciones que ventajas acá.
     */
    public function incluyeHora(string $horaActual): bool
    {
        return $horaActual >= $this->hora_desde && $horaActual <= $this->hora_hasta;
    }
}
