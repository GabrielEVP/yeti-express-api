<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reporte de Deudas del Cliente</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .client-info {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f5f5f5;
        }

        .summary {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9f9f9;
        }

        .status-pending {
            color: #ff0000;
        }

        .status-partial {
            color: #ffa500;
        }

        .status-paid {
            color: #008000;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Reporte de Deudas del Cliente</h1>
        <p>Fecha de generación: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="client-info">
        <h2>Información del Cliente</h2>
        <p><strong>Nombre:</strong> {{ $client->legal_name }}</p>
        <p><strong>Tipo:</strong> {{ $client->type }}</p>
        <p><strong>Número de Registro:</strong> {{ $client->registration_number }}</p>
    </div>

    <h2>Historial de Deudas</h2>
    <table>
        <thead>
            <tr>
                <th>Entrega</th>
                <th>Fecha</th>
                <th>Monto</th>
                <th>Estado</th>
                <th>Pagos Realizados</th>
                <th>Saldo Pendiente</th>
            </tr>
        </thead>
        <tbody>
            @foreach($client->debts as $debt)
                <tr>
                    <td>{{ $debt->delivery->number }}</td>
                    <td>{{ $debt->delivery->date->format('d/m/Y') }}</td>
                    <td>${{ number_format($debt->amount, 2) }}</td>
                    <td class="status-{{ $debt->status }}">
                        @if($debt->status === 'pending')
                            Pendiente
                        @elseif($debt->status === 'partial_paid')
                            Parcialmente Pagado
                        @else
                            Pagado
                        @endif
                    </td>
                    <td>
                        @foreach($debt->payments as $payment)
                            <div>
                                {{ $payment->date->format('d/m/Y') }} -
                                ${{ number_format($payment->amount, 2) }}
                            </div>
                        @endforeach
                    </td>
                    <td>
                        ${{ number_format($debt->amount - $debt->payments->sum('amount'), 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <h3>Resumen</h3>
        <p><strong>Total de Deudas:</strong> ${{ number_format($client->debts->sum('amount'), 2) }}</p>
        <p><strong>Total Pagado:</strong>
            ${{ number_format($client->debts->sum(function ($debt) {
    return $debt->payments->sum('amount'); }), 2) }}</p>
        <p><strong>Saldo Total Pendiente:</strong>
            ${{ number_format($client->debts->sum('amount') - $client->debts->sum(function ($debt) {
    return $debt->payments->sum('amount'); }), 2) }}
        </p>
    </div>
</body>

</html>