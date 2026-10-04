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
        'mp_store_id',
        'mp_pos_id',
        'mp_external_store_id',
        'mp_external_pos_id',
        'mp_qr_image_url',
        'mp_store_location',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expira_en' => 'datetime',
            'mp_store_location' => 'array',
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

    public function tienePosConfigurado(): bool
    {
        return $this->mp_pos_id !== null && $this->mp_external_pos_id !== null && $this->mp_qr_image_url !== null;
    }

    public function tokenVencido(): bool
    {
        return $this->token_expira_en !== null
            && now()->addMinutes(5)->greaterThanOrEqualTo($this->token_expira_en);
    }
}
