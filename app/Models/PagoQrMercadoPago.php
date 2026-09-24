<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoQrMercadoPago extends Model
{
    use HasFactory;

    protected $table = 'pagos_qr_mercadopago';

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_APROBADO = 'aprobado';

    public const ESTADO_RECHAZADO = 'rechazado';

    public const ESTADO_EXPIRADO = 'expirado';

    public const ESTADO_CANCELADO = 'cancelado';

    protected $fillable = [
        'empresa_id',
        'pedido_id',
        'user_id',
        'monto',
        'preference_id',
        'init_point',
        'payment_id',
        'external_reference',
        'estado',
        'expira_at',
        'datos_respuesta',
        'tipo_comprobante',
        'cliente_id',
        'cliente_nombre_nuevo',
        'cliente_cuit_nuevo',
        'cliente_telefono_nuevo',
        'tipo_factura',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'expira_at' => 'datetime',
            'datos_respuesta' => 'array',
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

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function expirado(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE && now()->greaterThan($this->expira_at);
    }
}
