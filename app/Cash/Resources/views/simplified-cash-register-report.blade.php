<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte Simplificado de Cajas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .subtitle {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .date-range {
            font-size: 14px;
            margin-bottom: 20px;
        }

        .summary-box {
            border: 2px solid #333;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 30px;
            background-color: #f9f9f9;
        }

        .summary-title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 15px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .amount {
            text-align: right;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 30px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }

        .positive {
            color: #28a745;
        }

        .negative {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">REPORTE SIMPLIFICADO DE CAJAS</div>
        <div class="date-range">Período: {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}</div>
    </div>

    @if(empty($daily_data) || $summary['total_delivered'] == 0)
        <div style="text-align: center; padding: 30px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; margin: 20px 0; color: #856404;">
            <h3 style="font-weight: bold; font-size: 16px;">No se encontraron datos</h3>
            <p>No hay movimientos de caja registrados en el período del {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}</p>
        </div>
    @endif

    <div class="summary-box">
        <div class="summary-title">RESUMEN GENERAL</div>
        <table>
            <tr>
                <th>Total Entregas</th>
                <td class="amount">{{ $summary['total_delivered'] }}</td>
            </tr>
            <tr>
                <th>Total Facturado</th>
                <td class="amount">${{ number_format($summary['total_invoiced'], 2) }}</td>
            </tr>
            <tr>
                <th>Total Cobrado</th>
                <td class="amount">${{ number_format($summary['total_collected'], 2) }}</td>
            </tr>
            <tr>
                <th>Total Gastos</th>
                <td class="amount">${{ number_format($summary['total_expenses'], 2) }}</td>
            </tr>
            <tr>
                <th>Balance Total</th>
                <td class="amount {{ $summary['total_balance'] >= 0 ? 'positive' : 'negative' }}">
                    <strong>${{ number_format($summary['total_balance'], 2) }}</strong>
                </td>
            </tr>
        </table>
    </div>

    <div class="summary-title">DETALLE DIARIO</div>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Entregas</th>
                <th>Facturado</th>
                <th>Cobrado</th>
                <th>Gastos</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($daily_data as $day)
                <tr>
                    <td>{{ $day['date'] }}</td>
                    <td class="amount">{{ $day['delivered'] }}</td>
                    <td class="amount">${{ number_format($day['invoiced'], 2) }}</td>
                    <td class="amount">${{ number_format($day['collected'], 2) }}</td>
                    <td class="amount">${{ number_format($day['expenses'], 2) }}</td>
                    <td class="amount {{ $day['balance'] >= 0 ? 'positive' : 'negative' }}">
                        ${{ number_format($day['balance'], 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th>TOTALES</th>
                <th class="amount">{{ $summary['total_delivered'] }}</th>
                <th class="amount">${{ number_format($summary['total_invoiced'], 2) }}</th>
                <th class="amount">${{ number_format($summary['total_collected'], 2) }}</th>
                <th class="amount">${{ number_format($summary['total_expenses'], 2) }}</th>
                <th class="amount {{ $summary['total_balance'] >= 0 ? 'positive' : 'negative' }}">
                    ${{ number_format($summary['total_balance'], 2) }}
                </th>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Reporte generado el {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
