<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrarEmpresaRequest;
use App\Mail\NuevaEmpresaRegistradaMailable;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class RegistroEmpresaController extends Controller
{
    public function create(): View
    {
        return view('empresas.registro');
    }

    public function store(RegistrarEmpresaRequest $request): View
    {
        $empresa = DB::transaction(function () use ($request): Empresa {
            $empresa = Empresa::create([
                'razon_social' => $request->validated('razon_social'),
                'cuit' => $request->validated('cuit'),
                'rubro' => $request->validated('rubro'),
                'email_contacto' => $request->validated('email_contacto'),
                'telefono' => $request->validated('telefono'),
                'direccion' => $request->validated('direccion'),
                'estado' => 'pendiente',
                // Rastro de aceptación de los Términos y Condiciones — la
                // validación (ver RegistrarEmpresaRequest) ya exige el
                // check antes de llegar acá, esto solo deja huella de
                // cuándo pasó.
                'terminos_aceptados_at' => now(),
            ]);

            User::create([
                'empresa_id' => $empresa->id,
                'role' => 'admin',
                'name' => $request->validated('admin_name'),
                'email' => $request->validated('admin_email'),
                'password' => $request->validated('admin_password'),
            ]);

            return $empresa;
        });

        $superadmin = User::where('role', 'superadmin')->first();

        // Best-effort: si el mail falla (SMTP caído, etc.) la empresa ya
        // quedó creada y el alta no se le puede caer a quien se está
        // registrando por un problema que es nuestro, no suyo. El aviso de
        // WhatsApp en registro-enviado.blade.php queda como respaldo.
        if ($superadmin?->email) {
            try {
                Mail::to($superadmin->email)->send(new NuevaEmpresaRegistradaMailable($empresa));
            } catch (\Throwable $e) {
                Log::warning('No se pudo avisar por email al superadmin de una empresa nueva registrada.', [
                    'empresa_id' => $empresa->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('empresas.registro-enviado', [
            'empresa' => $empresa,
            'superadmin' => $superadmin,
        ]);
    }
}
