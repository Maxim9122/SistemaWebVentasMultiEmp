<?php

namespace App\Models;

use App\Models\Concerns\TieneTelefonoWhatsapp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory, TieneTelefonoWhatsapp;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'cuit',
        'telefono',
        'email',
        'activo',
        'fecha_promesa_pago',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'fecha_promesa_pago' => 'date',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    public function ventasFiadas(): HasMany
    {
        return $this->ventas()->where('monto_fiado', '>', 0);
    }

    public function pagosCredito(): HasMany
    {
        return $this->hasMany(PagoCredito::class);
    }

    public function totalFiado(): float
    {
        return (float) $this->ventasFiadas()->sum('monto_fiado');
    }

    public function totalPagadoCredito(): float
    {
        $pagos = $this->pagosCredito()->selectRaw('COALESCE(SUM(monto_efectivo + monto_tarjeta + monto_transferencia), 0) as total')->first();

        return (float) $pagos->total;
    }

    /**
     * Lo que el cliente todavía debe: la suma de lo fiado en sus ventas menos
     * todo lo que pagó por Créditos. Se calcula al vuelo (no se guarda una
     * columna de saldo) para no tener que mantenerla sincronizada.
     */
    public function saldoPendiente(): float
    {
        return round($this->totalFiado() - $this->totalPagadoCredito(), 2);
    }

    /**
     * Para resaltar en naranja en la lista de deudores: la fecha de promesa
     * de pago está cargada y faltan 5 días o menos (incluye "vence hoy" y
     * "ya venció" — cuanto más cerca o más pasada la fecha, más urgente).
     */
    public function promesaPagoProxima(): bool
    {
        if (! $this->fecha_promesa_pago) {
            return false;
        }

        return now()->startOfDay()->diffInDays($this->fecha_promesa_pago->copy()->startOfDay(), false) <= 5;
    }
}
