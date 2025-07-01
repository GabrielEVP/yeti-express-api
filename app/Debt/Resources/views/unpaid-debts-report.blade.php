<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Deudas Pendientes</title>
    <link rel="stylesheet" href="{{ public_path('css/debt-report.css') }}">
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
    <div class="empty-state">
        <h2>No hay deudas pendientes</h2>
        <p>No se encontraron clientes con deudas pendientes en este momento.</p>
    </div>
@else
    @foreach($clients->getClients() as $client)
        <div class="client-section">
            <div class="client-header">
                Cliente: {{ $client->legalName }}
                @if($client->registrationNumber)
                    ({{ $client->registrationNumber }})
                @endif
            </div>

            @if(empty($client->debts))
                <div class="no-records">
                    <p>No hay deudas pendientes para este cliente.</p>
                </div>
            @else
                <table>
                    <thead>
                    <tr>
                        <th>Entrega</th>
                        <th>Fecha</th>
                        <th>Servicio</th>
                        <th>Monto Total</th>
                        <th>Pagado</th>
                        <th>Pendiente</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($client->debts as $debt)
                        <tr>
                            <td>{{ $debt['delivery']['number'] }}</td>
                            <td>{{ date('d/m/Y', strtotime($debt['delivery']['date'])) }}</td>
                            <td>{{ $debt['delivery']['service']['name'] }}</td>
                            <td class="amount">{{ number_format($debt['amount'], 2) }}</td>
                            <td class="amount">{{ number_format(collect($debt['payments'])->sum('amount'), 2) }}</td>
                            <td class="amount">{{ number_format($debt['amount'] - collect($debt['payments'])->sum('amount'), 2) }}</td>
                        </tr>
                        @if(!empty($debt['payments']))
                            <tr>
                                <td colspan="8">
                                    <div class="payments-section">
                                        <strong>Pagos realizados:</strong>
                                        <table>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Método</th>
                                                <th>Monto</th>
                                            </tr>
                                            @foreach($debt['payments'] as $payment)
                                                <tr>
                                                    <td>{{ date('d/m/Y', strtotime($payment['date'])) }}</td>
                                                    <td>{{ \App\Helpers\PaymentMethodTranslator::toSpanish($payment['method']) }}</td>
                                                    <td class="amount">{{ number_format($payment['amount'], 2) }}</td>
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
                    Deuda total pendiente: {{ number_format(collect($client->debts)->sum(function($debt) {
                            return $debt['amount'] - collect($debt['payments'])->sum('amount');
                        }), 2) }}
                </div>
            @endif
        </div>
    @endforeach

    <div class="debt-total">
        TOTAL DEUDAS PENDIENTES: {{ number_format(collect($clients->getClients())->sum(function($client) {
                return collect($client->debts)->sum(function($debt) {
                    return $debt['amount'] - collect($debt['payments'])->sum('amount');
                });
            }), 2) }}
    </div>
@endif

<div class="footer">
    Este reporte muestra las deudas pendientes o parcialmente pagadas de los clientes.
</div>
</body>
</html>
