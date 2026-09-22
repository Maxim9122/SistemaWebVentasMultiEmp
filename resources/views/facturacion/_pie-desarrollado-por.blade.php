@php
    $desarrolladoPor = \App\Models\User::where('role', 'superadmin')->first();
@endphp

<div style="margin-top: 8px; padding-top: 4px; border-top: 0.5px solid #ccc; text-align: center;">
    <p style="margin: 0; font-size: 7px; font-weight: normal; color: #999;">
        Desarrollado por LunaSoft
        @if ($desarrolladoPor?->telefono)
            · Tel: {{ $desarrolladoPor->telefono }}
        @endif
        @if ($desarrolladoPor?->email_publico)
            · {{ $desarrolladoPor->email_publico }}
        @endif
    </p>
</div>
