{{-- Estilos inline a propósito: este partial se incluye en varias
plantillas de ticket con hojas de estilo distintas, así no depende de que
cada una defina una clase igual. --}}
@if ($empresa->logoDataUri())
    <div style="text-align: center; margin: 2px 0 4px;">
        <img src="{{ $empresa->logoDataUri() }}" style="max-width: 60px; max-height: 35px; border-radius: 6px;" alt="Logo {{ $empresa->razon_social }}">
    </div>
@endif
