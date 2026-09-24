<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caja extends Model
{
    protected $fillable = [
        'empresa_id',
        'user_id',
        'abierta_at',
        'monto_apertura',
        'nota_apertura',
        'cerrada_at',
        'monto_cierre_declarado',
        'nota_cierre',
    ];

    protected function casts(): array
    {
        return [
            'abierta_at' => 'datetime',
            'cerrada_at' => 'datetime',
            'monto_apertura' => 'decimal:2',
            'monto_cierre_declarado' => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Pedido::class, 'caja_id');
    }

    public function egresos(): HasMany
    {
        return $this->hasMany(Egreso::class);
    }

    public function pagosCredito(): HasMany
    {
        return $this->hasMany(PagoCredito::class);
    }

    public function estaAbierta(): bool
    {
        return $this->cerrada_at === null;
    }

    public function totalVentasEfectivo(): float
    {
        return (float) $this->ventas()->sum('monto_efectivo');
    }

    public function totalVentasTarjeta(): float
    {
        return (float) $this->ventas()->sum('monto_tarjeta');
    }

    public function totalVentasTransferencia(): float
    {
        return (float) $this->ventas()->sum('monto_transferencia');
    }

    public function totalVentasMercadopago(): float
    {
        return (float) $this->ventas()->sum('monto_mercadopago');
    }

    public function totalEgresosEfectivo(): float
    {
        return (float) $this->egresos()->sum('monto_efectivo');
    }

    public function totalEgresosTransferencia(): float
    {
        return (float) $this->egresos()->sum('monto_transferencia');
    }

    public function totalPagosCreditoEfectivo(): float
    {
        return (float) $this->pagosCredito()->sum('monto_efectivo');
    }

    public function totalPagosCreditoTarjeta(): float
    {
        return (float) $this->pagosCredito()->sum('monto_tarjeta');
    }

    public function totalPagosCreditoTransferencia(): float
    {
        return (float) $this->pagosCredito()->sum('monto_transferencia');
    }

    /**
     * Lo que debería haber en efectivo en el cajón: el fondo inicial más lo
     * cobrado en efectivo (ventas y pagos de créditos recibidos en esta
     * caja), menos lo que salió en efectivo por egresos. La parte de
     * tarjeta/transferencia no forma parte del conteo físico.
     */
    public function efectivoEsperado(): float
    {
        return round(
            $this->monto_apertura + $this->totalVentasEfectivo() + $this->totalPagosCreditoEfectivo() - $this->totalEgresosEfectivo(),
            2
        );
    }

    /**
     * Positivo = sobró plata, negativo = faltó. Null mientras la caja sigue
     * abierta (todavía no se declaró un conteo de cierre).
     */
    public function diferencia(): ?float
    {
        if ($this->monto_cierre_declarado === null) {
            return null;
        }

        return round((float) $this->monto_cierre_declarado - $this->efectivoEsperado(), 2);
    }
}
