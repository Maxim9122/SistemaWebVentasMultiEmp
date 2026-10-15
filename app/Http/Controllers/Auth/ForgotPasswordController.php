<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Misma respuesta exista o no ese email en la base — no revela si
        // una cuenta está registrada o no.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'Si ese email está registrado, te mandamos un link para restablecer la contraseña.');
    }
}
