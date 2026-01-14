<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Alerta: Sirena fuera de línea</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #dc2626;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
        }
        .content {
            padding: 30px 20px;
            color: #333333;
        }
        .content h2 {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .sirena-info {
            background-color: #fef2f2;
            padding: 15px;
            border-left: 4px solid #dc2626;
            font-size: 16px;
            margin: 20px 0;
        }
        .footer {
            background-color: #f3f4f6;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Sirena Desconectada</h1>
        </div>
        <div class="content">
            <h2>Una sirena se encuentra fuera de línea</h2>
            <p>Por favor, verificar estado lo antes posible.</p>
            <div class="sirena-info">
                <strong>Sirena afectada:</strong> {{ $sirena }}
            </div>
            <p>Este mensaje fue generado automáticamente por el sistema de monitoreo de sirenas.</p>
        </div>
        <div class="footer">
            © 2025 AVE – Municipalidad de Guatemala.<br>
            Este es un mensaje automático. No responda a este correo.
        </div>
    </div>
</body>
</html>