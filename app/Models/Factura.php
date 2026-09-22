<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Factura extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_PENDIENTE_AFIP = 'pendiente_afip';

    public const ESTADO_APROBADA = 'aprobada';

    public const ESTADO_RECHAZADA = 'rechazada';

    public const ESTADO_ERROR = 'error';

    protected $fillable = [
        'empresa_id',
        'pedido_id',
        'cliente_id',
        'cliente_nombre',
        'cliente_cuit',
        'tipo_factura',
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
            'cae_vencimiento' => 'date',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function notaCredito(): HasOne
    {
        return $this->hasOne(NotaCredito::class);
    }

    public function fallo(): bool
    {
        return in_array($this->estado, [self::ESTADO_RECHAZADA, self::ESTADO_ERROR], true);
    }

    public function estaAprobada(): bool
    {
        return $this->estado === self::ESTADO_APROBADA && $this->cae !== null;
    }
}
