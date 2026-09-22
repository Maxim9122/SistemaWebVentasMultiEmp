<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color: #1e293b; font-size: 14px;">
    <p>Hola,</p>
    <p>Te compartimos {{ mb_strtolower($tituloDocumento) }} N° {{ $numero }} de <strong>{{ $empresaNombre }}</strong>, adjunto en PDF.</p>
    <p>Cualquier consulta, respondé este mismo email.</p>
    <p style="margin-top: 24px; color: #64748b;">{{ $empresaNombre }}</p>
</body>
</html>
