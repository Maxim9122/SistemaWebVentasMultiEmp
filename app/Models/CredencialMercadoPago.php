<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CredencialMercadoPago extends Model
{
    use HasFactory;

    protected $table = 'credenciales_mercadopago';

    public const ESTADO_PENDIENTE_ONBOARDING = 'pendiente_onboarding';

    public const ESTADO_ACTIVA = 'activa';

    protected $fillable = [
        'empresa_id',
        'ambiente',
        'mp_user_id',
        'access_token',
        'refresh_token',
        'token_expira_en',
        'estado',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expira_en' => 'datetime',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function estaActiva(): bool
    {
        return $this->estado === self::ESTADO_ACTIVA && $this->access_token !== null;
    }

    /**
     * El access_token de Mercado Pago vence (a diferencia de la api_key de
     * AFIP) — margen de 5 minutos para no arrancar a usar un token que
     * vence en el medio de la llamada.
     */
    public function tokenVencido(): bool
    {
        return $this->token_expira_en !== null && now()->addMinutes(5)->greaterThanOrEqualTo($this->token_expira_en);
    }
}
