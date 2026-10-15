{{-- Va dentro de la columna ".empresa" del encabezado, antes del <h1> con
la razón social. Estilos inline por el mismo motivo que _logo-ticket. --}}
@if ($empresa->logoDataUri())
    <img src="{{ $empresa->logoDataUri() }}" style="max-width: 160px; max-height: 60px; margin-bottom: 6px;" alt="Logo {{ $empresa->razon_social }}">
@endif
