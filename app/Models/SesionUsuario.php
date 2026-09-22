<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SesionUsuario extends Model
{
    protected $table = 'sesiones_usuario';

    protected $fillable = [
        'user_id',
        'empresa_id',
        'login_at',
        'ultima_actividad_at',
        'logout_at',
        'ip_address',
        'bloqueada',
        'motivo_bloqueo',
    ];

    // Minutos de inactividad tras los cuales CerrarSesionPorInactividad fuerza
    // el cierre de sesión (no aplica a admin/superadmin). También es la
    // ventana usada para decidir si una sesión sigue "ocupando cupo" de
    // cajero en ControlAccesoStaff.
    public const MINUTOS_INACTIVIDAD = 60;

    protected function casts(): array
    {
        return [
            'login_at' => 'datetime',
            'ultima_actividad_at' => 'datetime',
            'logout_at' => 'datetime',
            'bloqueada' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sinCierreRegistrado(): bool
    {
        return $this->logout_at === null;
    }

    /**
     * "Ocupando cupo" = sin cierre registrado y con actividad dentro de la
     * ventana de inactividad. Una sesión sin request nuevo desde hace más de
     * MINUTOS_INACTIVIDAD deja de contar acá aunque todavía no la haya
     * cerrado formalmente CerrarSesionPorInactividad (nadie mandó otro
     * request para que el middleware la detecte) — evita que una sesión
     * fantasma bloquee el cupo de otro cajero indefinidamente.
     */
    public function scopeOcupandoCupo(Builder $query): Builder
    {
        return $query->whereNull('logout_at')
            ->where('bloqueada', false)
            ->where('ultima_actividad_at', '>=', now()->subMinutes(self::MINUTOS_INACTIVIDAD));
    }

    public function duracionLegible(): ?string
    {
        if ($this->logout_at === null) {
            return null;
        }

        $minutos = $this->login_at->diffInMinutes($this->logout_at);
        $horas = intdiv($minutos, 60);
        $minutosRestantes = $minutos % 60;

        return $horas > 0 ? "{$horas}h {$minutosRestantes}m" : "{$minutosRestantes}m";
    }
}
