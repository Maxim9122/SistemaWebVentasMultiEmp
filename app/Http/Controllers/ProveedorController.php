<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarProveedorRequest;
use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProveedorController extends Controller
{
    public function index(Request $request): View
    {
        $proveedores = Proveedor::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->orderBy('nombre')
            ->get();

        return view('productos.proveedores.index', ['proveedores' => $proveedores]);
    }

    public function create(): View
    {
        return view('productos.proveedores.create');
    }

    public function store(GuardarProveedorRequest $request): RedirectResponse
    {
        Proveedor::create([
            ...$request->validated(),
            'empresa_id' => $request->user()->empresa_id,
            'activo' => true,
        ]);

        return redirect()->route('productos.proveedores.index')->with('status', 'Proveedor creado.');
    }

    public function edit(Request $request, Proveedor $proveedor): View
    {
        $this->autorizar($request, $proveedor);

        return view('productos.proveedores.edit', ['proveedor' => $proveedor]);
    }

    public function update(GuardarProveedorRequest $request, Proveedor $proveedor): RedirectResponse
    {
        $this->autorizar($request, $proveedor);

        $proveedor->update($request->validated());

        return redirect()->route('productos.proveedores.index')->with('status', 'Proveedor actualizado.');
    }

    public function activar(Request $request, Proveedor $proveedor): RedirectResponse
    {
        $this->autorizar($request, $proveedor);

        $proveedor->update(['activo' => true]);

        return back()->with('status', 'Proveedor activado.');
    }

    public function desactivar(Request $request, Proveedor $proveedor): RedirectResponse
    {
        $this->autorizar($request, $proveedor);

        $proveedor->update(['activo' => false]);

        return back()->with('status', 'Proveedor desactivado.');
    }

    private function autorizar(Request $request, Proveedor $proveedor): void
    {
        if ($proveedor->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }
    }
}
