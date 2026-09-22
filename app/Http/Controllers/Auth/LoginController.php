<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SesionUsuario;
use App\Services\ControlAccesoStaff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly ControlAccesoStaff $controlAcceso)
    {
    }

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Las credenciales no coinciden con ningún registro.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        // Se descarta explícitamente cualquier "volvé acá después de
        // loguearte" que haya quedado guardado de un intento anterior (ej.
        // una sesión vencida que falló justo en esa acción) — este sistema
        // siempre lleva a cada rol a su pantalla normal, nunca de vuelta a
        // lo que estaba haciendo antes de loguearse.
        $request->session()->forget('url.intended');

        $user = $request->user();

        if ($mensaje = $user->mensajeBloqueo()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => $mensaje])->onlyInput('email');
        }

        if ($motivo = $this->controlAcceso->evaluar($user, $request->ip())) {
            $this->controlAcceso->registrarIntentoBloqueado($user, $request->ip(), $motivo);

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => $motivo])->onlyInput('email');
        }

        // Se registra recién acá (no antes de los chequeos de bloqueo) para no
        // dejar en el historial una "sesión" de alguien a quien se le negó el
        // acceso — esos intentos quedan como fila aparte, marcada "bloqueada".
        $sesion = SesionUsuario::create([
            'user_id' => $user->id,
            'empresa_id' => $user->empresa_id,
            'login_at' => now(),
            'ultima_actividad_at' => now(),
            'ip_address' => $request->ip(),
        ]);
        $request->session()->put('sesion_usuario_id', $sesion->id);

        if ($user->esSuperadmin()) {
            return redirect()->route('superadmin.empresas.index');
        }

        if (in_array($user->role, ['vendedor', 'cajero_vendedor'], true)) {
            return redirect()->route('carritos.index');
        }

        if ($user->role === 'cajero') {
            return redirect()->route('caja.index');
        }

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $sesionId = $request->session()->get('sesion_usuario_id');

        if ($sesionId) {
            SesionUsuario::where('id', $sesionId)->update(['logout_at' => now()]);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
