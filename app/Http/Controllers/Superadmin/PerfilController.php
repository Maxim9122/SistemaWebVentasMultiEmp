<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function edit(Request $request): View
    {
        return view('superadmin.perfil.edit', ['usuario' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'email_publico' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        $usuario->update($datos);

        return redirect()->route('superadmin.perfil.edit')->with('status', 'Perfil actualizado.');
    }

    /**
     * Igual criterio que el logo de empresa: nunca svg (podría traer
     * <script>), 2MB alcanza para un ícono.
     */
    public function actualizarIcono(Request $request): RedirectResponse
    {
        $request->validate([
            'icono' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $usuario = $request->user();

        if ($usuario->icono_sitio_path) {
            Storage::disk('public')->delete($usuario->icono_sitio_path);
        }

        $path = $request->file('icono')->store('icono-sitio', 'public');

        $usuario->update(['icono_sitio_path' => $path]);

        return redirect()->route('superadmin.perfil.edit')->with('status', 'Ícono del sitio actualizado.');
    }

    public function eliminarIcono(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        if ($usuario->icono_sitio_path) {
            Storage::disk('public')->delete($usuario->icono_sitio_path);
            $usuario->update(['icono_sitio_path' => null]);
        }

        return redirect()->route('superadmin.perfil.edit')->with('status', 'Ícono del sitio eliminado.');
    }
}
