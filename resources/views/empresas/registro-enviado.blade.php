@extends('layouts.guest')

@section('titulo', 'Solicitud enviada')

@section('contenido')
    <div class="text-center space-y-4">
        <p class="text-lg font-semibold">¡Solicitud recibida!</p>
        <p class="text-sm text-slate-600">
            Tu solicitud de alta quedó pendiente de revisión. Te vamos a avisar por email cuando esté aprobada
            y ya puedas iniciar sesión.
        </p>

        @if ($telefonoSuperadmin = $superadmin?->telefonoSoloDigitos())
            @php
                $mensaje = "Hola! Acabo de registrar mi empresa \"{$empresa->razon_social}\" (CUIT {$empresa->cuit}) en ".config('app.name').". ¿Me la podés dar de alta?";
                $urlWhatsapp = 'https://wa.me/549'.$telefonoSuperadmin.'?text='.rawurlencode($mensaje);
            @endphp
            <a href="{{ $urlWhatsapp }}" target="_blank" rel="noopener"
                class="inline-flex items-center gap-2 rounded px-4 py-2 text-sm font-medium text-emerald-700 border border-emerald-300 hover:border-emerald-400 hover:bg-emerald-50">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4 fill-current">
                    <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm0 18.15h-.01a8.2 8.2 0 0 1-4.19-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.23 8.23 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.26-8.24a8.2 8.2 0 0 1 5.84 2.42 8.19 8.19 0 0 1 2.42 5.83c0 4.55-3.7 8.24-8.27 8.24Zm4.52-6.17c-.25-.12-1.47-.72-1.7-.81-.23-.08-.39-.12-.56.13-.17.24-.64.8-.78.97-.14.17-.29.19-.53.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.24-.02-.38.11-.5.11-.11.25-.29.37-.43.12-.14.16-.24.25-.4.08-.17.04-.31-.02-.44-.06-.12-.56-1.35-.77-1.85-.2-.48-.41-.42-.56-.42-.14-.01-.31-.01-.48-.01a.92.92 0 0 0-.67.31c-.23.24-.87.85-.87 2.08 0 1.22.89 2.4 1.02 2.57.12.17 1.75 2.67 4.24 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.55.1.47-.07 1.47-.6 1.68-1.19.21-.58.21-1.08.14-1.19-.06-.1-.23-.16-.48-.28Z"/>
                </svg>
                Avisarle por WhatsApp
            </a>
        @endif

        <a href="{{ route('login') }}" class="block mt-2 text-sm font-medium text-slate-900 hover:underline">
            Volver al login
        </a>
    </div>
@endsection
