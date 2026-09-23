<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Venta (experimento React) · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/venta-react/app.jsx'])
</head>
<body class="bg-slate-50 text-slate-900">
    <div id="venta-react-root"
        data-nombre-vendedor="{{ auth()->user()->name }}"
        data-url-carritos="{{ route('carritos.index') }}"></div>
</body>
</html>
