<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntentoPagoMercadopago extends Model
{
    use HasFactory;

    protected $table = 'intentos_pago_mercadopago';

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_APROBADO = 'aprobado';

    public const ESTADO_EXPIRADO = 'expirado';

    public const ESTADO_CANCELADO = 'cancelado';

    /**
     * Mercado Pago confirmó el pago de verdad, pero al intentar cerrar la
     * venta localmente (ProcesadorDeCobro::cobrar()) algo lo bloqueó que no
     * es "ya estaba cobrado" — ej: el cajero cerró su caja entre que se
     * generó el QR y el cliente pagó, o se quedó sin stock mientras tanto.
     * No se marca como "aprobado" porque la venta en realidad NO se cerró
     * — hay plata real recibida sin reflejar en el sistema, necesita
     * revisión manual.
     */
    public const ESTADO_ERROR_AL_COBRAR = 'error_al_cobrar';

    protected $fillable = [
        'empresa_id',
        'pedido_id',
        'user_id',
        'monto',
        'external_reference',
        'mp_merchant_order_id',
        'estado',
        'expira_at',
        'montos_json',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'expira_at' => 'datetime',
            'montos_json' => 'array',
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

    public function cajero(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function expirado(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE
            && $this->expira_at !== null
            && now()->greaterThan($this->expira_at);
    }
}
