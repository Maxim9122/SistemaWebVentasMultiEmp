<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Services\Facturacion\Exceptions\CertificadoYaValidadoException;
use App\Services\Facturacion\Exceptions\CuitYaRegistradoException;
use App\Services\Facturacion\OnboardingFacturacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConfiguracionEmpresaController extends Controller
{
    public function edit(Request $request): View
    {
        return view('configuracion.edit', ['empresa' => $request->user()->empresa->load('credencialFacturacion')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'condicion_fiscal' => [
                'nullable',
                Rule::in([Empresa::CONDICION_RESPONSABLE_INSCRIPTO, Empresa::CONDICION_MONOTRIBUTISTA]),
            ],
            'comprobante_predeterminado' => [
                'required',
                Rule::in([Empresa::COMPROBANTE_REMITO, 'A', 'B', 'C']),
            ],
            'ajuste_efectivo_porcentaje' => ['required', 'numeric', 'between:-100,100'],
            'ajuste_tarjeta_porcentaje' => ['required', 'numeric', 'between:-100,100'],
            'ajuste_transferencia_porcentaje' => ['required', 'numeric', 'between:-100,100'],
            'descuento_precio_empleado_porcentaje' => ['nullable', 'numeric', 'between:0,100'],
        ]);

        $request->user()->empresa->update([
            ...$datos,
            'permite_multiples_carritos' => $request->boolean('permite_multiples_carritos'),
            'permite_cambiar_precio_venta' => $request->boolean('permite_cambiar_precio_venta'),
            'controla_stock' => $request->boolean('controla_stock'),
            'permite_cajero_modificar_ventas' => $request->boolean('permite_cajero_modificar_ventas'),
            'permite_cajero_notas_credito' => $request->boolean('permite_cajero_notas_credito'),
            'factura_habilitada' => $request->boolean('factura_habilitada'),
            'mostrar_modal_comprobante' => $request->boolean('mostrar_modal_comprobante'),
            'permite_fiado' => $request->boolean('permite_fiado'),
        ]);

        return redirect()->route('configuracion.edit')->with('status', 'Configuración guardada.');
    }

    public function actualizarDatos(Request $request, OnboardingFacturacionService $onboarding): RedirectResponse
    {
        $empresa = $request->user()->empresa;

        $datos = $request->validate([
            'razon_social' => ['required', 'string', 'max:255'],
            'cuit' => ['required', 'string', 'max:20', Rule::unique('empresas', 'cuit')->ignore($empresa->id)],
            'email_contacto' => ['required', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:255'],
        ]);

        // razon_social/cuit/email_contacto son los únicos que la API de facturación
        // conoce (se mandaron en el alta) — si cambiaron y ya hay un registro del
        // otro lado, hay que corregirlo ahí primero. telefono/direccion son solo
        // locales, nunca se mandaron, se guardan siempre sin pasar por la API.
        $camposFacturacion = collect($datos)
            ->only(['razon_social', 'cuit', 'email_contacto'])
            ->filter(fn ($valor, $campo) => $valor !== $empresa->{$campo})
            ->all();

        $credencial = $empresa->credencialFacturacion;

        if ($camposFacturacion !== [] && $credencial?->empresa_externa_id) {
            try {
                $onboarding->corregirDatos($credencial, $camposFacturacion);
            } catch (CertificadoYaValidadoException $e) {
                return back()->withErrors(['datos_facturacion' => $e->getMessage()])->withInput();
            } catch (CuitYaRegistradoException $e) {
                return back()->withErrors(['cuit' => $e->getMessage()])->withInput();
            } catch (\Throwable $e) {
                report($e);

                return back()->withErrors([
                    'datos_facturacion' => 'No se pudo sincronizar la corrección con la API de facturación. Probá de nuevo en unos minutos.',
                ])->withInput();
            }
        }

        $empresa->update($datos);

        return redirect()->route('configuracion.edit')->with('status', 'Datos de la empresa guardados.');
    }
}
