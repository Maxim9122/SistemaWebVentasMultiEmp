<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color: #1e293b; font-size: 14px;">
    <p>Hola,</p>
    <p>Se registró una empresa nueva y quedó pendiente de revisión:</p>

    <table style="margin: 16px 0; border-collapse: collapse;">
        <tr>
            <td style="padding: 2px 10px 2px 0; color: #64748b;">Razón social:</td>
            <td><strong>{{ $empresa->razon_social }}</strong></td>
        </tr>
        @if ($empresa->cuit)
            <tr>
                <td style="padding: 2px 10px 2px 0; color: #64748b;">CUIT:</td>
                <td>{{ \App\Models\Empresa::formatearCuit($empresa->cuit) }}</td>
            </tr>
        @endif
        @if ($empresa->rubro)
            <tr>
                <td style="padding: 2px 10px 2px 0; color: #64748b;">Rubro:</td>
                <td>{{ $empresa->rubro }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding: 2px 10px 2px 0; color: #64748b;">Email de contacto:</td>
            <td>{{ $empresa->email_contacto }}</td>
        </tr>
        @if ($empresa->telefono)
            <tr>
                <td style="padding: 2px 10px 2px 0; color: #64748b;">Teléfono:</td>
                <td>{{ $empresa->telefono }}</td>
            </tr>
        @endif
    </table>

    <p><a href="{{ $urlRevision }}" style="color: #0f172a; font-weight: bold;">Ver y aprobar en el panel &rarr;</a></p>
</body>
</html>
