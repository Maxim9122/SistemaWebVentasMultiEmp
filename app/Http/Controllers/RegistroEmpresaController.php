<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrarEmpresaRequest;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegistroEmpresaController extends Controller
{
    public function create(): View
    {
        return view('empresas.registro');
    }

    public function store(RegistrarEmpresaRequest $request): View
    {
        DB::transaction(function () use ($request): void {
            $empresa = Empresa::create([
                'razon_social' => $request->validated('razon_social'),
                'cuit' => $request->validated('cuit'),
                'rubro' => $request->validated('rubro'),
                'email_contacto' => $request->validated('email_contacto'),
                'telefono' => $request->validated('telefono'),
                'direccion' => $request->validated('direccion'),
                'estado' => 'pendiente',
            ]);

            User::create([
                'empresa_id' => $empresa->id,
                'role' => 'admin',
                'name' => $request->validated('admin_name'),
                'email' => $request->validated('admin_email'),
                'password' => $request->validated('admin_password'),
            ]);
        });

        return view('empresas.registro-enviado');
    }
}
