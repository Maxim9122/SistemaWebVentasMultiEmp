<?php

namespace App\Http\Controllers;

use App\Models\TurnoLaboral;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SeguridadAccesoController extends Controller
{
    public function edit(Request $request): View
    {
        $empresa = $request->user()->empresa;

        $turnosPorDia = $empresa->turnosLaborales()
            ->orderBy('hora_desde')
            ->get()
            ->groupBy('dia_semana');

        return view('staff.seguridad', [
            'empresa' => $empresa,
            'turnosPorDia' => $turnosPorDia,
            'dias' => TurnoLaboral::DIAS,
        ]);
    }

    public function actualizarIp(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'ip_permitida' => ['nullable', 'ip'],
        ]);

        $request->user()->empresa->update(['ip_permitida' => $datos['ip_permitida'] ?? null]);

        return redirect()->route('staff.seguridad.edit')->with('status', $datos['ip_permitida']
            ? 'IP autorizada guardada.'
            : 'Restricción de IP quitada: cualquier conexión puede ingresar.');
    }

    public function actualizarHorarioHabilitado(Request $request): RedirectResponse
    {
        $request->user()->empresa->update(['horario_laboral_habilitado' => $request->boolean('horario_laboral_habilitado')]);

        return redirect()->route('staff.seguridad.edit')->with('status', $request->boolean('horario_laboral_habilitado')
            ? 'Horario laboral y cupo de cajeros activados.'
            : 'Horario laboral y cupo de cajeros desactivados — el staff puede loguearse sin restricción de horario. Los turnos que ya cargaste no se borraron.');
    }

    public function guardarTurno(Request $request): RedirectResponse
    {
        $empresa = $request->user()->empresa;

        $datos = $request->validate([
            'dias' => ['required', 'array', 'min:1'],
            'dias.*' => ['integer', 'between:1,7'],
            'hora_desde' => ['required', 'date_format:H:i'],
            'hora_hasta' => ['required', 'date_format:H:i', 'after:hora_desde'],
            'limite_cajeros' => ['nullable', 'integer', 'min:1'],
        ]);

        $diasConDosTurnos = $empresa->turnosLaborales()
            ->whereIn('dia_semana', $datos['dias'])
            ->get()
            ->groupBy('dia_semana')
            ->filter(fn ($turnos) => $turnos->count() >= 2)
            ->keys()
            ->map(fn ($dia) => TurnoLaboral::DIAS[$dia])
            ->implode(', ');

        if ($diasConDosTurnos !== '') {
            return back()->withErrors([
                'dias' => "Ya hay 2 turnos cargados para: {$diasConDosTurnos}. Borrá uno antes de agregar otro para ese día.",
            ])->withInput();
        }

        DB::transaction(function () use ($empresa, $datos) {
            foreach ($datos['dias'] as $dia) {
                $empresa->turnosLaborales()->create([
                    'dia_semana' => $dia,
                    'hora_desde' => $datos['hora_desde'],
                    'hora_hasta' => $datos['hora_hasta'],
                    'limite_cajeros' => $datos['limite_cajeros'] ?? null,
                ]);
            }
        });

        return redirect()->route('staff.seguridad.edit')->with('status', 'Horario guardado.');
    }

    public function eliminarTurno(Request $request, TurnoLaboral $turno): RedirectResponse
    {
        if ($turno->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }

        $turno->delete();

        return redirect()->route('staff.seguridad.edit')->with('status', 'Turno eliminado.');
    }
}
