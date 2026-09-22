<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaCredito extends Model
{
    use HasFactory;

    // Mismo vocabulario de estados que Factura (misma máquina de estados,
    // literales propios para no acoplar los dos modelos entre sí).
    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_PENDIENTE_AFIP = 'pendiente_afip';

    public const ESTADO_APROBADA = 'aprobada';

    public const ESTADO_RECHAZADA = 'rechazada';

    public const ESTADO_ERROR = 'error';

    protected $table = 'notas_credito';

    protected $fillable = [
        'empresa_id',
        'factura_id',
        'creado_por',
        'importe_acreditado',
        'motivo',
        'numero_comprobante',
        'comprobante_externo_id',
        'cae',
        'cae_vencimiento',
        'estado',
        'error_mensaje',
    ];

    protected function casts(): array
    {
        return [
            'importe_acreditado' => 'decimal:2',
            'cae_vencimiento' => 'date',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function fallo(): bool
    {
        return in_array($this->estado, [self::ESTADO_RECHAZADA, self::ESTADO_ERROR], true);
    }
}
