<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Acceder') · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ \App\Models\User::iconoSitioUrlEstatico() ?? asset('favicon.ico') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
    <meta name="theme-color" content="#0f172a">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-900 min-h-screen flex flex-col">
    <header class="shrink-0 bg-slate-900 text-white px-6 py-4 flex items-center justify-center gap-2">
        @if ($iconoSitio = \App\Models\User::iconoSitioUrlEstatico())
            <img src="{{ $iconoSitio }}" alt="{{ config('app.name') }}" class="w-8 h-8 object-cover rounded-lg">
        @endif
        <span class="text-lg font-semibold">
            {{ config('app.name') }}<sup class="text-[10px] font-normal text-slate-400 ml-0.5">TM</sup>
        </span>
    </header>

    <main class="flex justify-center py-8">
        <div class="w-full @yield('ancho', 'max-w-sm') px-4">
            <div class="bg-white rounded-lg shadow p-6">
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
            </div>
        </div>
    </main>

    @php $lunasoft = \App\Models\User::where('role', 'superadmin')->first(); @endphp
    <footer class="shrink-0 border-t bg-white px-6 py-3 text-xs text-slate-500 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-center">
        <span>&copy; {{ now()->year }} LunaSoft</span>
        @if ($lunasoft && ($lunasoft->telefono || $lunasoft->email_publico))
            @if ($lunasoft->telefono)
                <span>Tel: {{ $lunasoft->telefono }}</span>
            @endif
            @if ($lunasoft->email_publico)
                <span>{{ $lunasoft->email_publico }}</span>
            @endif
        @endif
    </footer>

    @include('partials.evitar-doble-envio')
    @include('partials.registro-service-worker')
</body>
</html>
