<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CredencialFacturacion extends Model
{
    use HasFactory;

    protected $table = 'credenciales_facturacion';

    public const ESTADO_PENDIENTE_ONBOARDING = 'pendiente_onboarding';

    public const ESTADO_ACTIVA = 'activa';

    protected $fillable = [
        'empresa_id',
        'ambiente',
        'punto_venta',
        'api_key',
        'webhook_secret',
        'estado',
        'onboarding_id',
        'empresa_externa_id',
    ];

    protected $hidden = [
        'api_key',
        'webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'webhook_secret' => 'encrypted',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function estaActiva(): bool
    {
        return $this->estado === self::ESTADO_ACTIVA
            && $this->api_key !== null
            && $this->punto_venta !== null;
    }
}
