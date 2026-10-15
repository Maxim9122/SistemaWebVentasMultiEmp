@extends('layouts.guest')

@section('titulo', 'Términos y Condiciones')
@section('ancho', 'max-w-3xl')

@section('contenido')
    <div class="text-sm text-slate-700 leading-relaxed space-y-4">
        <div>
            <h1 class="text-xl font-semibold text-slate-900 mb-1">Términos y Condiciones de uso</h1>
            <p class="text-xs text-slate-400">Última actualización: 14 de octubre de 2026</p>
        </div>

        <p>
            Estos Términos y Condiciones regulan el uso de {{ config('app.name') }} (en adelante, "el Sistema"),
            una herramienta de gestión comercial (ventas, stock, clientes, facturación electrónica y similares)
            ofrecida por LunaSoft (en adelante, "el Titular") a comercios y empresas (en adelante, "la Empresa" o
            "vos") que se registran para usarla. Al registrar una empresa en el Sistema, estás aceptando estos
            términos en su totalidad.
        </p>

        <div>
            <h2 class="text-base font-semibold text-slate-900 mb-1.5">1. Qué es el Sistema</h2>
            <p>
                El Sistema es una herramienta de software que la Empresa utiliza para gestionar su propia actividad
                comercial: cargar productos, clientes, registrar ventas, emitir comprobantes (remitos y, si
                corresponde, facturas electrónicas ante AFIP) y administrar su caja. El Titular ofrece
                exclusivamente el servicio de gestión (la plataforma de software) — no participa, interviene ni
                tiene ningún rol en las operaciones comerciales reales que la Empresa realiza con sus propios
                clientes.
            </p>
        </div>

        <div>
            <h2 class="text-base font-semibold text-slate-900 mb-1.5">2. Responsabilidad sobre ventas, facturación y cobros</h2>
            <p class="mb-2">
                Todas las ventas, facturas, remitos, cobros, créditos (fiado) y cualquier otra operación comercial
                que la Empresa registre o emita a través del Sistema son exclusiva responsabilidad de la Empresa. El
                Titular no es parte de esas operaciones, no garantiza su validez, cobrabilidad ni resultado, y no
                asume ninguna responsabilidad por errores, omisiones o consecuencias derivadas de cómo la Empresa
                usa el Sistema para vender, cobrar o facturar. El Titular tampoco garantiza ningún resultado de
                ventas, ingresos o cobros de ninguna clase.
            </p>
            <p>
                Cuando el Sistema asiste a la Empresa para emitir facturas electrónicas ante AFIP, lo hace como una
                herramienta técnica: la exactitud de los datos fiscales (CUIT, condición frente al IVA, importes,
                condición fiscal propia, etc.) y el cumplimiento de las obligaciones impositivas correspondientes
                son responsabilidad exclusiva de la Empresa. El Titular no brinda asesoramiento contable, impositivo
                ni legal.
            </p>
        </div>

        <div>
            <h2 class="text-base font-semibold text-slate-900 mb-1.5">3. Datos cargados en el Sistema</h2>
            <p class="mb-2">
                Todos los datos que la Empresa carga en el Sistema —incluyendo, sin limitarse a, datos de sus
                productos, precios, stock, y datos de sus propios clientes (nombre, CUIT, contacto, historial de
                compras, saldos de cuenta corriente, etc.)— son de propiedad y responsabilidad exclusiva de la
                Empresa. La Empresa es la única responsable de contar con las bases legales necesarias para
                recolectar y tratar esos datos, de usarlos de forma lícita, y de cumplir con la normativa de
                protección de datos personales y defensa del consumidor que le resulte aplicable en su relación con
                sus propios clientes. El manejo, uso y destino de esos datos queda a libre criterio de cada Empresa.
            </p>
            <p>
                El Titular almacena estos datos únicamente como parte de la prestación del servicio de gestión
                (para que el Sistema funcione), sin utilizarlos para fines propios ajenos a esa prestación.
            </p>
        </div>

        <div>
            <h2 class="text-base font-semibold text-slate-900 mb-1.5">4. Disponibilidad y funcionamiento del Sistema</h2>
            <p>
                El Titular pone a disposición el Sistema "tal cual está", haciendo sus mejores esfuerzos para que
                funcione de forma correcta y esté disponible, pero sin garantizar que su funcionamiento sea
                ininterrumpido o esté libre de errores. En la medida permitida por la ley, el Titular no será
                responsable por daños o perjuicios indirectos, pérdida de ganancias, de ventas o de datos, que
                puedan derivarse del uso o la imposibilidad de uso del Sistema.
            </p>
        </div>

        <div>
            <h2 class="text-base font-semibold text-slate-900 mb-1.5">5. Uso de las cuentas de usuario</h2>
            <p>
                La Empresa es responsable de los usuarios (administradores, cajeros, vendedores) que da de alta
                dentro de su cuenta, de las credenciales de acceso de cada uno, y de las acciones que esos usuarios
                realicen dentro del Sistema.
            </p>
        </div>

        <div>
            <h2 class="text-base font-semibold text-slate-900 mb-1.5">6. Cambios a estos términos</h2>
            <p>
                El Titular puede actualizar estos Términos y Condiciones en el futuro. Los cambios relevantes se
                van a comunicar por los medios de contacto que la Empresa tenga cargados en el Sistema.
            </p>
        </div>

        <p class="text-xs text-slate-400 pt-2 border-t">
            Este documento es una referencia general y no reemplaza el asesoramiento de un profesional legal.
        </p>
    </div>

    <div class="mt-6 text-center">
        <a href="{{ url()->previous() }}" class="text-sm text-slate-600 hover:underline">
            &larr; Volver
        </a>
    </div>
@endsection
