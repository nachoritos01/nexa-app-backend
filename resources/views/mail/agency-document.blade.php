<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentType }} {{ $number }}</title>
</head>
<body style="font-family: 'Helvetica', 'Arial', sans-serif; color: #333; line-height: 1.6;">
    <div style="max-width: 560px; margin: 0 auto; padding: 24px;">
        <h2 style="color: #1e40af; margin-bottom: 4px;">{{ $businessName }}</h2>
        <p>Estimado/a {{ $clientName }},</p>
        <p>
            Adjunto encontrará la {{ mb_strtolower($documentType) }}
            <strong>{{ $number }}</strong> en formato PDF.
        </p>
        <p>Quedamos a su disposición para cualquier consulta.</p>
        <p style="margin-top: 24px;">Saludos cordiales,<br>{{ $businessName }}</p>
    </div>
</body>
</html>
