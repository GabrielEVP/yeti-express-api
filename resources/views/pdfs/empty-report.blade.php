<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Caja - Sin Datos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            text-align: center;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }

        .message {
            font-size: 16px;
            margin-bottom: 30px;
            color: #555;
        }

        .period {
            font-weight: bold;
            color: #007bff;
        }

        .icon {
            font-size: 48px;
            margin-bottom: 20px;
            color: #999;
        }

        .suggestion {
            margin-top: 30px;
            font-style: italic;
            color: #666;
        }

        .footer {
            margin-top: 40px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📊</div>
        <h1>No hay datos disponibles</h1>
        <div class="message">
            {{ $message }}
        </div>
        <div class="period">
            Período consultado: {{ $start_date }} - {{ $end_date }}
        </div>
        <div class="suggestion">
            Pruebe seleccionando un rango de fechas diferente o verifique que existan operaciones registradas en el sistema.
        </div>
        <div class="footer">
            Generado el: {{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}
        </div>
    </div>
</body>
</html>
