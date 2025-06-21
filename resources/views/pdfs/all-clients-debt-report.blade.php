<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reporte de Deudas de Todos los Clientes</title>
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

        .date-range {
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .client-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .client-header {
            background-color: #f5f5f5;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
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
            font-size: 11px;
        }

        th {
            background-color: #f5f5f5;
        }

        .summary {
            margin-top: 10px;
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

        .grand-total {
            margin-top: 30px;
            border-top: 2px solid #000;
            padding-top: 10px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Reporte de Deudas de Todos los Clientes</h1>
        <p>Fecha de generación: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="date-range">
        <p>Período: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
    </div>

    @php
        $grandTotalDebt = 0;
        $grandTotalPaid = 0;
        $grandTotalPending = 0;
    @endphp

    @foreach($clients as $client)
        @php
            $clientTotalDebt = $client->debts->sum('amount');
            $clientTotalPaid = $client->debts->sum(function ($debt) {
                return $debt->payments->sum('amount');
            });
            $clientTotalPending = $clientTotalDebt - $clientTotalPaid;

            $grandTotalDebt += $clientTotalDebt;
            $grandTotalPaid += $clientTotalPaid;
            $grandTotalPending += $clientTotalPending;
        @endphp

        <div class="client-section">
            <div class="client-header">
                <h2>{{ $client->legal_name }}</h2>
                <p><strong>Tipo:</strong> {{ $client->type }} | <strong>Número de Registro:</strong> {{ $client->registration_number }}</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Entrega</th>
                        <th>Fecha</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th>Pagos</th>
                        <th>Pendiente</th>
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
                                    Parcial
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
                <p><strong>Total de Deudas:</strong> ${{ number_format($clientTotalDebt, 2) }}</p>
                <p><strong>Total Pagado:</strong> ${{ number_format($clientTotalPaid, 2) }}</p>
                <p><strong>Saldo Pendiente:</strong> ${{ number_format($clientTotalPending, 2) }}</p>
            </div>
        </div>
    @endforeach

    <div class="grand-total">
        <h3>Resumen General</h3>
        <p><strong>Total de Deudas:</strong> ${{ number_format($grandTotalDebt, 2) }}</p>
        <p><strong>Total Pagado:</strong> ${{ number_format($grandTotalPaid, 2) }}</p>
        <p><strong>Saldo Total Pendiente:</strong> ${{ number_format($grandTotalPending, 2) }}</p>
    </div>
</body>

</html>
