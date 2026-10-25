{{-- Va dentro de la columna ".empresa" del encabezado, antes del <h1> con
la razón social. Estilos inline por el mismo motivo que _logo-ticket. --}}
@if ($empresa->logoDataUri())
    <img src="{{ $empresa->logoDataUri() }}" style="max-width: 80px; max-height: 30px; margin-bottom: 6px; border-radius: 6px;" alt="Logo {{ $empresa->razon_social }}">
@endif
