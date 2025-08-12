<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Deudas Pendientes - {{ $client->legal_name }}</title>
    <style>
        {!! file_get_contents(base_path('app/Debt/Resources/css/debt.css')) !!}
    </style>
</head>
<body>
<div class="header">
    <div class="title">REPORTE DE DEUDAS PENDIENTES</div>
    <div class="subtitle">Cliente: {{ $client->legal_name }}</div>
    @if($client->registration_number)
        <div class="subtitle">({{ $client->registration_number }})</div>
    @endif
</div>

<div class="date">
    Generado el: {{ $generatedAt->format('d/m/Y H:i:s') }}
</div>

<div class="client-info">
    <h2>Información del Cliente</h2>
    <p><strong>Nombre:</strong> {{ $client->legal_name }}</p>
    <p><strong>Tipo:</strong> {{ $client->type }}</p>
    @if($client->registration_number)
        <p><strong>Número de Registro:</strong> {{ $client->registration_number }}</p>
    @endif
</div>

@if($client->debts->isEmpty())
    <div class="empty-state">
        <h2>No hay deudas pendientes</h2>
        <p>Este cliente no tiene deudas pendientes en este momento.</p>
    </div>
@else
    <div class="client-section">
        <table>
            <thead>
            <tr>
                <th>Entrega</th>
                <th>Fecha</th>
                <th>Servicio</th>
                <th>Monto Total</th>
                <th>Pagado</th>
                <th>Pendiente</th>
                <th>Estado</th>
            </tr>
            </thead>
            <tbody>
            @foreach($client->debts as $debt)
                @php
                    $paid = $debt->payments->sum('amount');
                    $pending = $debt->amount - $paid;
                @endphp
                <tr>
                    <td>{{ $debt->delivery->number }}</td>
                    <td>{{ $debt->delivery->date->format('d/m/Y') }}</td>
                    <td>{{ $debt->delivery->service->name }}</td>
                    <td class="amount">${{ number_format($debt->amount, 2) }}</td>
                    <td class="amount">${{ number_format($paid, 2) }}</td>
                    <td class="amount">${{ number_format($pending, 2) }}</td>
                    <td>
                        @if($debt->status === 'pending')
                            Pendiente
                        @elseif($debt->status === 'partial_paid')
                            Parcialmente Pagado
                        @else
                            Pagado
                        @endif
                    </td>
                </tr>

                @if($debt->payments->isNotEmpty())
                    <tr>
                        <td colspan="7">
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
                                            <td>{{ $payment->date ? $payment->date->format('d/m/Y') : $payment->created_at->format('d/m/Y') }}</td>
                                            <td>{{ \App\Debt\Helpers\MethodTranslator::toSpanish($payment->method) }}</td>
                                            <td class="amount">${{ number_format($payment->amount, 2) }}</td>
                                            <td>{{ $payment->notes ?? '-' }}</td>
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

        <div class="summary">
            <h3>Resumen</h3>
            <p><strong>Total de Deudas:</strong>
                ${{ number_format($client->debts->sum('amount'), 2) }}
            </p>
            <p><strong>Total Pagado:</strong>
                ${{ number_format($client->debts->sum(function ($debt) { return $debt->payments->sum('amount'); }), 2) }}
            </p>
            <p><strong>Saldo Total Pendiente:</strong>
                ${{ number_format($client->debts->sum(function($debt) { return $debt->amount - $debt->payments->sum('amount'); }), 2) }}
            </p>
        </div>
    </div>
@endif

<div class="footer">
    Este reporte muestra las deudas pendientes o parcialmente pagadas del cliente.
</div>
</body>
</html>
