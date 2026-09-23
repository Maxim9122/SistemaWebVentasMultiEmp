<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Panel') · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ \App\Models\User::iconoSitioUrlEstatico() ?? asset('favicon.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900">
    <div class="h-screen flex overflow-hidden">
        <div id="fondo_menu" onclick="cerrarMenu()" class="hidden fixed inset-0 bg-black/50 z-30 md:hidden"></div>

        <aside id="menu_lateral" class="w-60 shrink-0 bg-slate-900 text-slate-200 flex flex-col overflow-y-auto
            fixed inset-y-0 left-0 z-40 -translate-x-full transition-transform duration-200
            md:static md:translate-x-0 md:z-auto">
            <div class="px-5 py-4 border-b border-slate-800 flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <div class="text-lg font-semibold text-white">
                        {{ config('app.name') }}<sup class="text-[10px] font-normal text-slate-400 ml-0.5">TM</sup>
                    </div>
                    @if (auth()->user()?->empresa)
                        <div class="mt-3 flex items-center gap-2 min-w-0">
                            @if ($logoEmpresa = auth()->user()->empresa->logoUrl())
                                <img src="{{ $logoEmpresa }}" alt="Logo de {{ auth()->user()->empresa->razon_social }}"
                                    class="w-12 h-12 object-cover rounded-xl border border-slate-700 shrink-0">
                            @endif
                            <span class="text-sm font-medium text-slate-200 truncate">{{ auth()->user()->empresa->razon_social }}</span>
                        </div>
                    @endif
                </div>
                <button type="button" onclick="cerrarMenu()" class="md:hidden shrink-0 text-slate-400 hover:text-white p-1" aria-label="Cerrar menú">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
                @if (auth()->user()?->esSuperadmin())
                    <a href="{{ route('superadmin.empresas.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('superadmin.empresas.*') ? 'bg-slate-800 text-white' : '' }}">Empresas</a>
                    <a href="{{ route('superadmin.perfil.edit') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('superadmin.perfil.*') ? 'bg-slate-800 text-white' : '' }}">Mi perfil</a>
                @else
                    <a href="{{ route('dashboard') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : '' }}">Resumen</a>
                    <a href="{{ route('ayuda.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('ayuda.*') ? 'bg-slate-800 text-white' : '' }}">Ayuda</a>
                    @if (in_array(auth()->user()->role, ['vendedor', 'cajero_vendedor']))
                        <a href="{{ route('carritos.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('carritos.*') ? 'bg-slate-800 text-white' : '' }}">Carritos</a>
                    @endif
                    @if (in_array(auth()->user()->role, ['cajero', 'cajero_vendedor']))
                        <a href="{{ route('caja.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('caja.*') ? 'bg-slate-800 text-white' : '' }}">Caja</a>
                        <a href="{{ route('caja-sesion.show') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('caja-sesion.*') ? 'bg-slate-800 text-white' : '' }}">Mi caja</a>
                    @endif
                    @if (in_array(auth()->user()->role, ['vendedor', 'cajero', 'cajero_vendedor']))
                        <a href="{{ route('pedidos.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('pedidos.*') ? 'bg-slate-800 text-white' : '' }}">Pedidos</a>
                    @endif
                    @if (in_array(auth()->user()->role, ['admin', 'cajero', 'cajero_vendedor']))
                        <a href="{{ route('ventas.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('ventas.*') ? 'bg-slate-800 text-white' : '' }}">Ventas</a>
                        <a href="{{ route('presupuestos.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('presupuestos.*') ? 'bg-slate-800 text-white' : '' }}">Presupuestos</a>
                        <a href="{{ route('creditos.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('creditos.*') ? 'bg-slate-800 text-white' : '' }}">Créditos</a>
                    @endif
                    @if (auth()->user()->role === 'admin')
                        <a href="{{ route('productos.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('productos.*') ? 'bg-slate-800 text-white' : '' }}">Productos</a>
                        <a href="{{ route('clientes.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('clientes.*') ? 'bg-slate-800 text-white' : '' }}">Clientes</a>
                        <a href="{{ route('staff.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('staff.*') ? 'bg-slate-800 text-white' : '' }}">Staff</a>
                        <a href="{{ route('egresos.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('egresos.*') ? 'bg-slate-800 text-white' : '' }}">Egresos</a>
                        <a href="{{ route('cajas.index') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('cajas.*') ? 'bg-slate-800 text-white' : '' }}">Historial de cajas</a>
                        <a href="{{ route('configuracion.edit') }}" class="block rounded px-3 py-2 hover:bg-slate-800 {{ request()->routeIs('configuracion.*') ? 'bg-slate-800 text-white' : '' }}">Configuración</a>
                    @endif
                @endif
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="shrink-0 bg-white border-b px-4 md:px-6 py-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <button type="button" onclick="abrirMenu()" class="md:hidden shrink-0 text-slate-600 hover:text-slate-900 p-1 -ml-1" aria-label="Abrir menú">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h1 class="text-xl font-semibold truncate">@yield('titulo', 'Panel')</h1>
                </div>
                @auth
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-slate-500">{{ auth()->user()->empresa->razon_social ?? auth()->user()->email }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-sm text-slate-500 hover:text-slate-900 hover:underline">Cerrar sesión</button>
                        </form>
                    </div>
                @endauth
            </header>

            <main class="flex-1 overflow-y-auto p-6">
                @if (session('status'))
                    <div class="mb-4 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('contenido')
            </main>

            @php $lunasoft = \App\Models\User::where('role', 'superadmin')->first(); @endphp
            <footer class="shrink-0 border-t bg-white px-6 py-3 text-xs text-slate-500 flex items-center justify-between">
                <span>&copy; {{ now()->year }} LunaSoft</span>
                @if ($lunasoft && ($lunasoft->telefono || $lunasoft->email_publico))
                    <span class="flex items-center gap-3">
                        @if ($lunasoft->telefono)
                            <span>Tel: {{ $lunasoft->telefono }}</span>
                        @endif
                        @if ($lunasoft->email_publico)
                            <span>{{ $lunasoft->email_publico }}</span>
                        @endif
                    </span>
                @endif
            </footer>
        </div>
    </div>

    @if (session('pedido_comprobante_listo_id') && auth()->user()?->empresa?->mostrar_modal_comprobante)
        @php $pedidoListo = \App\Models\Pedido::with('factura')->find(session('pedido_comprobante_listo_id')); @endphp
        @if ($pedidoListo && ($pedidoListo->tipo_comprobante === 'remito' || $pedidoListo->factura?->cae))
            @include('partials.modal-comprobante-listo', ['pedidoListo' => $pedidoListo])
        @endif
    @endif

    @php $cajaAbiertaEgreso = auth()->check() && in_array(auth()->user()->role, ['cajero', 'cajero_vendedor']) ? auth()->user()->cajaAbierta() : null; @endphp
    @if ($cajaAbiertaEgreso)
        @php
            $empresaEgreso = auth()->user()->empresa;
            $empresaEgreso->asegurarMotivosEgresoPorDefecto();
        @endphp
        @include('partials.boton-egresos', [
            'caja' => $cajaAbiertaEgreso,
            'motivosEgreso' => $empresaEgreso->motivosEgreso()->where('activo', true)->orderBy('nombre')->get(),
            'proveedoresEgreso' => \App\Models\Proveedor::where('empresa_id', $empresaEgreso->id)->where('activo', true)->orderBy('nombre')->get(),
            'staffEgreso' => \App\Models\User::where('empresa_id', $empresaEgreso->id)->where('role', '!=', 'admin')->where('activo', true)->orderBy('name')->get(),
            'productosEgreso' => \App\Models\Producto::where('empresa_id', $empresaEgreso->id)->where('activo', true)->orderBy('nombre')->get(),
        ])
    @endif

    <script>
        function abrirMenu() {
            document.getElementById('menu_lateral').classList.remove('-translate-x-full');
            document.getElementById('fondo_menu').classList.remove('hidden');
        }
        function cerrarMenu() {
            document.getElementById('menu_lateral').classList.add('-translate-x-full');
            document.getElementById('fondo_menu').classList.add('hidden');
        }
    </script>
</body>
</html>
