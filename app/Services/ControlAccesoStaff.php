<?php

namespace App\Services;

use App\Models\SesionUsuario;
use App\Models\User;
use Carbon\Carbon;

/**
 * Chequeos de seguridad que se evalúan solo al momento de loguearse (no en
 * cada request): IP autorizada, horario laboral y cupo de cajeros por turno.
 * El admin y el superadmin quedan siempre exentos de los tres.
 */
class ControlAccesoStaff
{
    /**
     * Devuelve el motivo por el que se rechaza el login, o null si está permitido.
     */
    public function evaluar(User $usuario, string $ip, ?Carbon $momento = null): ?string
    {
        if ($usuario->esSuperadmin() || $usuario->role === 'admin') {
            return null;
        }

        $empresa = $usuario->empresa;

        if (! $empresa) {
            return null;
        }

        $momento ??= now();

        if ($empresa->ip_permitida && $ip !== $empresa->ip_permitida) {
            return 'Este equipo no está autorizado para ingresar al sistema. Contactate con el administrador de tu empresa.';
        }

        if (! $empresa->horarioLaboralHabilitado()) {
            return null;
        }

        $turnosHoy = $empresa->turnosLaborales()
            ->where('dia_semana', $momento->dayOfWeekIso)
            ->get();

        if ($turnosHoy->isEmpty()) {
            return 'Hoy no está habilitado el acceso al sistema según el horario laboral configurado. Contactate con el administrador si creés que esto es un error.';
        }

        $horaActual = $momento->format('H:i:s');
        $turnoActual = $turnosHoy->first(fn ($turno) => $turno->incluyeHora($horaActual));

        if (! $turnoActual) {
            return 'Estás fuera del horario laboral habilitado para ingresar al sistema.';
        }

        if (in_array($usuario->role, ['cajero', 'cajero_vendedor'], true) && $turnoActual->limite_cajeros !== null) {
            $ocupados = SesionUsuario::query()
                ->ocupandoCupo()
                ->where('empresa_id', $empresa->id)
                ->whereHas('user', fn ($q) => $q->whereIn('role', ['cajero', 'cajero_vendedor']))
                ->count();

            if ($ocupados >= $turnoActual->limite_cajeros) {
                return 'Se alcanzó el límite de cajeros habilitados para este turno. Contactate con el administrador.';
            }
        }

        return null;
    }

    public function registrarIntentoBloqueado(User $usuario, string $ip, string $motivo): void
    {
        SesionUsuario::create([
            'user_id' => $usuario->id,
            'empresa_id' => $usuario->empresa_id,
            'login_at' => now(),
            'logout_at' => now(),
            'ip_address' => $ip,
            'bloqueada' => true,
            'motivo_bloqueo' => $motivo,
        ]);
    }
}
