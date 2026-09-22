<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

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
     * Los 10 dígitos "de WhatsApp" (área + número, sin 0 ni 15 ni el 54/9 de
     * país) para precargar el campo de envío por WhatsApp — sea cual sea el
     * formato en que se haya cargado `telefono` (con o sin código de país,
     * con espacios/guiones), alcanza con quedarse con los últimos 10 dígitos.
     * Devuelve null si no hay teléfono cargado.
     */
    public function telefonoSoloDigitos(): ?string
    {
        if (blank($this->telefono)) {
            return null;
        }

        $digitos = preg_replace('/\D+/', '', $this->telefono);

        return $digitos !== '' ? substr($digitos, -10) : null;
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
