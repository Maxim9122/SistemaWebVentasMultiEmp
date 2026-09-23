<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_publico',
        'telefono',
        'password',
        'empresa_id',
        'role',
        'activo',
        'puede_cambiar_precio_venta',
        'icono_sitio_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'puede_cambiar_precio_venta' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cajas(): HasMany
    {
        return $this->hasMany(Caja::class);
    }

    public function cajaAbierta(): ?Caja
    {
        return $this->cajas()->whereNull('cerrada_at')->first();
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'vendedor_id');
    }

    public function esSuperadmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function iconoSitioUrl(): ?string
    {
        return $this->icono_sitio_path ? Storage::disk('public')->url($this->icono_sitio_path) : null;
    }

    /**
     * El ícono configurable vive en el superadmin (es del sitio entero, no
     * por empresa) — se resuelve así, en vez de depender de quién está
     * logueado, para que se vea igual en /login y en cualquier página,
     * esté quien esté (o nadie) autenticado.
     */
    public static function iconoSitioUrlEstatico(): ?string
    {
        return static::where('role', 'superadmin')->first()?->iconoSitioUrl();
    }

    public function empresaActiva(): bool
    {
        return $this->esSuperadmin() || $this->empresa?->estaActiva() === true;
    }

    /**
     * El admin siempre puede si la empresa tiene la función habilitada —
     * mismo criterio que el resto de los permisos de este proyecto (ej.
     * editar ventas, anular facturas: el admin nunca depende de un flag
     * individual, solo el staff). El flag `puede_cambiar_precio_venta` es
     * por persona y se gestiona desde Staff, que a propósito no lista
     * admins — así que sin este caso especial, el admin nunca tendría
     * forma de dárselo a sí mismo.
     */
    public function puedeCambiarPrecioVenta(): bool
    {
        if ($this->empresa?->permite_cambiar_precio_venta !== true) {
            return false;
        }

        return $this->role === 'admin' || $this->puede_cambiar_precio_venta;
    }

    public function puedeIniciarSesion(): bool
    {
        return $this->esSuperadmin() || ($this->activo && $this->empresaActiva());
    }

    /**
     * Motivo por el que no puede acceder, o null si puede.
     */
    public function mensajeBloqueo(): ?string
    {
        if ($this->puedeIniciarSesion()) {
            return null;
        }

        if (! $this->activo) {
            return 'Tu cuenta fue desactivada. Contactate con el administrador de tu empresa.';
        }

        return match ($this->empresa?->estado) {
            'pendiente' => 'Tu solicitud de alta todavía está en revisión.',
            'rechazada' => 'Tu solicitud de alta fue rechazada.',
            'suspendida' => 'Tu cuenta está suspendida. Contactate con el administrador.',
            default => 'No podés acceder al sistema en este momento.',
        };
    }
}
