<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte Simplificado de Cajas</title>
    <style>
        {!! file_get_contents(base_path('app/Cash/Resources/css/SimplifiedCash.css')) !!}
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
