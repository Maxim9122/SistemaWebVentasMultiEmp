@extends('layouts.app')

@section('titulo', 'Seguridad de acceso')

@section('contenido')
    <div class="mb-4">
        <a href="{{ route('staff.index') }}" class="text-sm text-slate-500 hover:underline">&larr; Volver a Staff</a>
    </div>

    <div class="mb-6 rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-600">
        Estas configuraciones controlan quién puede loguearse, desde dónde y cuándo. Ninguna afecta a tu usuario
        <strong>admin</strong>: vos podés entrar siempre, desde cualquier lugar y a cualquier hora. Solo aplican a
        cajero, vendedor y cajero_vendedor.
    </div>

    {{-- IP autorizada --}}
    <div class="bg-white rounded-lg shadow p-5 mb-6">
        <h2 class="text-base font-semibold mb-1">IP autorizada</h2>
        <p class="text-sm text-slate-500 mb-4">
            Si cargás una IP acá, el staff solo va a poder loguearse desde una conexión con esa IP (por ejemplo, la de
            internet de tu local). Si intentan entrar desde otro lado, el acceso se rechaza y queda registrado como
            intento bloqueado en el <a href="{{ route('staff.sesiones') }}" class="underline">historial de sesiones</a>.
            Dejalo vacío para no restringir nada. Ojo: si tu proveedor de internet te cambia la IP con el tiempo
            (algo común), vas a tener que volver a esta pantalla y actualizarla, o el staff va a quedar bloqueado sin
            que hayan hecho nada malo.
        </p>

        <p class="text-sm text-slate-600 mb-3">
            Estado actual:
            @if ($empresa->ip_permitida)
                <span class="text-xs font-medium rounded px-2 py-1 bg-emerald-100 text-emerald-800">Restringido a {{ $empresa->ip_permitida }}</span>
            @else
                <span class="text-xs font-medium rounded px-2 py-1 bg-slate-200 text-slate-700">Sin restricción</span>
            @endif
        </p>

        <form method="POST" action="{{ route('staff.seguridad.ip.update') }}" class="flex flex-wrap items-end gap-2">
            @csrf
            @method('PUT')
            <div>
                <label for="ip_permitida" class="block text-xs text-slate-500 mb-1">IP autorizada</label>
                <input type="text" id="ip_permitida" name="ip_permitida" value="{{ old('ip_permitida', $empresa->ip_permitida) }}"
                    placeholder="ej: 190.123.45.67"
                    class="w-48 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
            </div>
            <button type="button" onclick="document.getElementById('ip_permitida').value = '{{ request()->ip() }}'"
                class="rounded px-3 py-2 text-sm font-medium text-slate-600 border border-slate-300 hover:border-slate-400">
                Usar la IP de esta conexión ({{ request()->ip() }})
            </button>
            <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                Guardar
            </button>
        </form>
        @if ($empresa->ip_permitida)
            <form method="POST" action="{{ route('staff.seguridad.ip.update') }}" class="mt-2">
                @csrf
                @method('PUT')
                <input type="hidden" name="ip_permitida" value="">
                <button type="submit" class="text-sm text-red-600 hover:underline">Quitar restricción</button>
            </form>
        @endif
    </div>

    {{-- Horario laboral --}}
    <div class="bg-white rounded-lg shadow p-5 mb-6">
        <h2 class="text-base font-semibold mb-1">Horario laboral y cupo de cajeros</h2>
        <div class="text-sm text-slate-500 mb-4 space-y-2">
            <p>
                Configurá los días y horarios en los que el staff puede loguearse. Un turno es un rango horario
                (ej: 09:00 a 13:00) que aplica a uno o varios días de la semana que elijas con los checks, así no
                tenés que cargar el mismo horario varias veces. Cada día puede tener hasta 2 turnos (por ejemplo,
                mañana y tarde con un corte al mediodía).
            </p>
            <p>
                Podés cargar y editar los turnos con esta función apagada, sin que afecten a nadie todavía — recién
                se aplican cuando tildás el check de abajo. Así podés preparar el horario con calma y activarlo
                cuando quieras, o apagarlo un rato (por una changa puntual, un feriado, etc.) sin perder lo ya
                cargado.
            </p>
        </div>

        <form method="POST" action="{{ route('staff.seguridad.horario.update') }}" class="mb-5">
            @csrf
            @method('PUT')
            <label class="flex items-center gap-2 text-sm font-medium">
                <input type="checkbox" name="horario_laboral_habilitado" value="1" onchange="this.form.submit()"
                    @checked($empresa->horario_laboral_habilitado) class="rounded border border-slate-300">
                Trabajar con restricción de horario y cupo de cajeros
            </label>
        </form>

        @if ($empresa->horario_laboral_habilitado)
            <div class="rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 mb-5">
                <strong>Activado.</strong> Los días de la semana que no tengan ningún turno configurado quedan
                completamente bloqueados para el staff (nadie va a poder loguearse ese día). Si querés que un día
                quede libre, cubrí los 7 días o desmarcá el check de arriba.
                @if ($turnosPorDia->isEmpty())
                    <br><strong>Ojo:</strong> todavía no cargaste ningún turno — con el check tildado así, nadie del
                    staff va a poder loguearse ningún día hasta que cargues al menos uno.
                @endif
            </div>
        @else
            <div class="rounded border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 mb-5">
                Desactivado: el staff puede loguearse sin restricción de horario ni de cupo de cajeros, sea cual sea
                la configuración de abajo.
            </div>
        @endif

        <div class="text-sm text-slate-500 mb-4 space-y-2">
            <p>
                El <strong>límite de cajeros</strong> es opcional y es por turno: si lo dejás vacío, no hay límite de
                cajeros para ese horario. Si lo completás (ej: 1), una vez que esa cantidad de cajeros (cuenta
                cajero y cajero_vendedor) están logueados y activos al mismo tiempo, un cajero adicional que intente
                entrar en ese turno va a ser rechazado y va a quedar registrado como intento bloqueado. No hay límite
                de vendedores: solo pueden generar pedidos/ventas pendientes de cobro, así que si entra alguien de
                más no hay riesgo real — el cajero puede borrar esas ventas antes de cobrar, y vos podés revisar el
                historial de sesiones para actuar sobre esa persona si hizo falta.
            </p>
            <p>
                Una sesión deja de "ocupar" su cupo de cajero automáticamente si pasó más de 1 hora sin actividad
                (ver más abajo el cierre por inactividad) — así un cajero que se olvidó de cerrar sesión el día
                anterior no le tapa el lugar a otro al día siguiente.
            </p>
        </div>

        <form method="POST" action="{{ route('staff.seguridad.turnos.store') }}" class="border rounded-lg p-4 mb-5 bg-slate-50">
            @csrf
            <p class="text-sm font-medium mb-2">Agregar turno</p>

            <div class="flex flex-wrap gap-3 mb-3">
                @foreach ($dias as $numero => $nombre)
                    <label class="flex items-center gap-1.5 text-sm">
                        <input type="checkbox" name="dias[]" value="{{ $numero }}" @checked(collect(old('dias'))->contains($numero))>
                        {{ $nombre }}
                    </label>
                @endforeach
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="hora_desde" class="block text-xs text-slate-500 mb-1">Desde</label>
                    <input type="time" id="hora_desde" name="hora_desde" value="{{ old('hora_desde') }}"
                        class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label for="hora_hasta" class="block text-xs text-slate-500 mb-1">Hasta</label>
                    <input type="time" id="hora_hasta" name="hora_hasta" value="{{ old('hora_hasta') }}"
                        class="rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
                <div>
                    <label for="limite_cajeros" class="block text-xs text-slate-500 mb-1">Límite de cajeros (opcional)</label>
                    <input type="number" id="limite_cajeros" name="limite_cajeros" min="1" value="{{ old('limite_cajeros') }}"
                        placeholder="Sin límite" class="w-36 rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500">
                </div>
                <button type="submit" class="rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800">
                    Guardar turno
                </button>
            </div>
        </form>

        <div class="divide-y">
            @forelse ($dias as $numero => $nombre)
                <div class="py-3 flex items-start justify-between gap-4">
                    <p class="text-sm font-medium w-24 shrink-0">{{ $nombre }}</p>
                    <div class="flex-1">
                        @forelse ($turnosPorDia->get($numero, collect()) as $turno)
                            <div class="flex items-center gap-3 text-sm text-slate-600 mb-1">
                                <span>{{ substr($turno->hora_desde, 0, 5) }} a {{ substr($turno->hora_hasta, 0, 5) }}</span>
                                <span class="text-xs text-slate-400">
                                    {{ $turno->limite_cajeros ? "Máx. {$turno->limite_cajeros} cajero(s)" : 'Sin límite de cajeros' }}
                                </span>
                                <form method="POST" action="{{ route('staff.seguridad.turnos.destroy', $turno) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:underline">Eliminar</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm text-slate-400">
                                {{ $empresa->horario_laboral_habilitado ? 'Bloqueado — sin turno configurado' : 'Sin restricción (función desactivada)' }}
                            </p>
                        @endforelse
                    </div>
                </div>
            @empty
            @endforelse
        </div>
    </div>

    {{-- Cierre por inactividad --}}
    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="text-base font-semibold mb-1">Cierre de sesión por inactividad</h2>
        <p class="text-sm text-slate-500">
            Esto ya está activo y no requiere configuración: si un cajero, vendedor o cajero_vendedor pasa más de
            <strong>1 hora sin usar el sistema</strong> (sin importar el motivo — se fue, se cortó la luz, cerró la
            pestaña), su sesión se cierra sola en cuanto vuelva a intentar algo, y queda liberado cualquier cupo de
            cajero que estuviera ocupando. Tu usuario admin no tiene este límite.
        </p>
    </div>
@endsection
