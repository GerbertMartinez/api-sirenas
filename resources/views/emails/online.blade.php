<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Notificación: Sirena en línea</title>
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
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        .header {
            background-color: #16a34a;
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
            background-color: #ecfdf5;
            padding: 15px;
            border-left: 4px solid #16a34a;
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
            <h1>✅ Sirena en línea</h1>
        </div>
        <div class="content">
            <h2>Una sirena ha restablecido su conexión</h2>
            <p>La sirena está nuevamente en línea y lista para activar.</p>
            <div class="sirena-info">
                <strong>Sirena:</strong> {{ $sirena }}
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