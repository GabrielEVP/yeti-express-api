<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Deudas Pendientes</title>
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
            margin-bottom: 20px;
        }
        .date {
            font-size: 12px;
            text-align: right;
            margin-bottom: 20px;
        }
        .client-section {
            margin-bottom: 25px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 15px;
        }
        .client-header {
            font-size: 14px;
            font-weight: bold;
            background-color: #f5f5f5;
            padding: 5px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            font-size: 11px;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .amount {
            text-align: right;
        }
        .debt-total {
            font-weight: bold;
            text-align: right;
            margin-top: 10px;
        }
        .payments-section {
            margin-left: 20px;
            margin-bottom: 10px;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 30px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        .no-debts {
            text-align: center;
            font-style: italic;
            margin: 50px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">REPORTE DE DEUDAS PENDIENTES</div>
        <div class="subtitle">Listado de clientes con deudas por pagar</div>
    </div>

    <div class="date">
        Generado el: {{ $generatedAt->format('d/m/Y H:i:s') }}
    </div>

    @if($clients->isEmpty())
        <div class="no-debts">
            No hay clientes con deudas pendientes en este momento.
        </div>
    @else
        @foreach($clients as $client)
            <div class="client-section">
                <div class="client-header">
                    Cliente: {{ $client->legal_name }}
                    @if($client->registration_number)
                        ({{ $client->registration_number }})
                    @endif
                </div>

                @if($client->debts->isEmpty())
                    <p>No hay deudas pendientes para este cliente.</p>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>ID Deuda</th>
                                <th>Entrega</th>
                                <th>Fecha</th>
                                <th>Servicio</th>
                                <th>Estado</th>
                                <th>Monto Total</th>
                                <th>Pagado</th>
                                <th>Pendiente</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($client->debts as $debt)
                                <tr>
                                    <td>{{ $debt->id }}</td>
                                    <td>{{ $debt->delivery->number }}</td>
                                    <td>{{ $debt->delivery->date->format('d/m/Y') }}</td>
                                    <td>{{ $debt->delivery->service->name }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $debt->status)) }}</td>
                                    <td class="amount">{{ number_format($debt->amount, 2) }}</td>
                                    <td class="amount">{{ number_format($debt->payments->sum('amount'), 2) }}</td>
                                    <td class="amount">{{ number_format($debt->amount - $debt->payments->sum('amount'), 2) }}</td>
                                </tr>
                                @if($debt->payments->isNotEmpty())
                                    <tr>
                                        <td colspan="8">
                                            <div class="payments-section">
                                                <strong>Pagos realizados:</strong>
                                                <table>
                                                    <tr>
                                                        <th>Fecha</th>
                                                        <th>Método</th>
                                                        <th>Monto</th>
                                                        <th>Notas</th>
                                                    </tr>
                                                    @foreach($debt->payments as $payment)
                                                        <tr>
                                                            <td>{{ $payment->date->format('d/m/Y') }}</td>
                                                            <td>{{ $payment->payment_method }}</td>
                                                            <td class="amount">{{ number_format($payment->amount, 2) }}</td>
                                                            <td>{{ $payment->notes }}</td>
                                                        </tr>
                                                    @endforeach
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>

                    <div class="debt-total">
                        Deuda total pendiente: {{ number_format($client->debts->sum(function($debt) {
                            return $debt->amount - $debt->payments->sum('amount');
                        }), 2) }}
                    </div>
                @endif
            </div>
        @endforeach

        <div class="debt-total">
            TOTAL DEUDAS PENDIENTES: {{ number_format($clients->sum(function($client) {
                return $client->debts->sum(function($debt) {
                    return $debt->amount - $debt->payments->sum('amount');
                });
            }), 2) }}
        </div>
    @endif

    <div class="footer">
        Este reporte muestra las deudas pendientes o parcialmente pagadas de los clientes.
    </div>
</body>
</html>
