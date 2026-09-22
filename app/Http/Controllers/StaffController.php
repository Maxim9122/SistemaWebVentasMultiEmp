<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarStaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $staff = User::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->where('role', '!=', 'admin')
            ->orderBy('name')
            ->get();

        return view('staff.index', ['staff' => $staff]);
    }

    public function create(): View
    {
        return view('staff.create');
    }

    public function store(GuardarStaffRequest $request): RedirectResponse
    {
        User::create([
            'empresa_id' => $request->user()->empresa_id,
            'role' => $request->validated('role'),
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'puede_cambiar_precio_venta' => $request->boolean('puede_cambiar_precio_venta'),
            'activo' => true,
        ]);

        return redirect()->route('staff.index')->with('status', 'Usuario creado.');
    }

    public function edit(Request $request, User $staff): View
    {
        $this->autorizar($request, $staff);

        return view('staff.edit', ['staff' => $staff]);
    }

    public function update(GuardarStaffRequest $request, User $staff): RedirectResponse
    {
        $this->autorizar($request, $staff);

        $datos = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'role' => $request->validated('role'),
            'puede_cambiar_precio_venta' => $request->boolean('puede_cambiar_precio_venta'),
        ];

        if ($request->validated('password')) {
            $datos['password'] = $request->validated('password');
        }

        $staff->update($datos);

        return redirect()->route('staff.index')->with('status', 'Usuario actualizado.');
    }

    public function activar(Request $request, User $staff): RedirectResponse
    {
        $this->autorizar($request, $staff);

        $staff->update(['activo' => true]);

        return back()->with('status', 'Usuario activado.');
    }

    public function desactivar(Request $request, User $staff): RedirectResponse
    {
        $this->autorizar($request, $staff);

        $staff->update(['activo' => false]);

        return back()->with('status', 'Usuario desactivado.');
    }

    private function autorizar(Request $request, User $staff): void
    {
        if ($staff->empresa_id !== $request->user()->empresa_id || $staff->role === 'admin') {
            abort(404);
        }
    }
}
