<?php

use App\Http\Middleware\CerrarSesionPorInactividad;
use App\Http\Middleware\EnsureCuentaActiva;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'cuenta.activa' => EnsureCuentaActiva::class,
            'sesion.actividad' => CerrarSesionPorInactividad::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sesión vencida, ya sea detectada por CSRF (form que quedó abierto
        // de antes, token viejo → Laravel la mapea a 419 antes de que
        // corra cualquier render() registrado, por eso se intercepta acá
        // con respond(), sobre la respuesta ya armada) o por "no autenticado"
        // al navegar (ej. un link, sin CSRF de por medio). En los dos casos:
        // limpieza total de sesión (mismo criterio que ya usan
        // CerrarSesionPorInactividad/EnsureCuentaActiva) y vuelta prolija al
        // login con aviso — nunca la página 419 genérica de Laravel, y nunca
        // dejando guardado un "url.intended" que reintente la misma acción
        // que recién falló apenas el usuario se loguee de nuevo.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $sesionVencida = $response->getStatusCode() === 419 || $e instanceof AuthenticationException;

            if (! $sesionVencida || $request->expectsJson()) {
                return $response;
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Tu sesión expiró. Volvé a iniciar sesión.',
            ]);
        });
    })->create();
