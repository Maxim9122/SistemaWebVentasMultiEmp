<?php

namespace App\Http\Middleware;

use App\Models\SesionUsuario;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * El admin queda exento a pedido explícito del usuario del proyecto — el resto
 * del staff se desloguea solo tras 60 minutos sin ningún request. También es
 * el mecanismo que libera el cupo de cajeros por turno sin necesitar un cron:
 * la propia consulta de cupo ignora sesiones con actividad vencida (ver
 * SesionUsuario::scopeOcupandoCupo), esto solo se encarga de dejarlas
 * formalmente cerradas y de sacar a la persona de encima del sistema.
 */
class CerrarSesionPorInactividad
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->esSuperadmin() || $user->role === 'admin') {
            return $next($request);
        }

        $sesionId = $request->session()->get('sesion_usuario_id');

        if ($sesionId) {
            $sesion = SesionUsuario::find($sesionId);

            if ($sesion && $sesion->logout_at === null) {
                if ($sesion->ultima_actividad_at
                    && $sesion->ultima_actividad_at->diffInMinutes(now()) > SesionUsuario::MINUTOS_INACTIVIDAD) {
                    $sesion->update(['logout_at' => $sesion->ultima_actividad_at]);

                    Auth::guard('web')->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->withErrors([
                        'email' => 'Tu sesión se cerró por inactividad (más de 1 hora sin uso). Volvé a iniciar sesión.',
                    ]);
                }

                $sesion->update(['ultima_actividad_at' => now()]);
            }
        }

        return $next($request);
    }
}
