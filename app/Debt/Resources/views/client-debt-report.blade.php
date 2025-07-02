<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Deudas del Cliente</title>
    <style>
        {!! file_get_contents(base_path('app/Debt/Resources/css/debt.css')) !!}
    </style>
</head>
<body>
<div class="header">
    <h1>Reporte de Deudas del Cliente</h1>
    <p>Incluye deudas pendientes y parcialmente pagadas</p>
    <p>Fecha de generación: {{ now()->format('d/m/Y H:i:s') }}</p>
</div>

<div class="date-range">
    <p>Período: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}
        - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
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
    @forelse($client->debts as $debt)
        <tr>
            <td>{{ $debt->delivery->number }}</td>
            <td>{{ $debt->delivery->date->format('d/m/Y') }}</td>
            <td>${{ number_format($debt->amount, 2) }}</td>
            <td class="status-{{ str_replace('_', '-', $debt->status) }}">
                @if($debt->status === 'pending')
                    Pendiente
                @elseif($debt->status === 'partial_paid')
                    Parcialmente Pagado
                @else
                    Pagado
                @endif
            </td>
            <td>
                @forelse($debt->payments as $payment)
                    <div>
                        {{ $payment->date->format('d/m/Y') }} -
                        ${{ number_format($payment->amount, 2) }}
                    </div>
                @empty
                    <div>Sin pagos</div>
                @endforelse
            </td>
            <td>
                ${{ number_format($debt->amount - $debt->payments->sum('amount'), 2) }}
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="6" style="text-align: center;">No hay deudas pendientes en el período seleccionado</td>
        </tr>
    @endforelse
    </tbody>
</table>

<div class="summary">
    <h3>Resumen</h3>
    <p><strong>Total de Deudas:</strong>
        ${{ number_format($client->debts->sum('amount'), 2) }}
    </p>
    <p><strong>Total Pagado en Deudas Parciales:</strong>
        ${{ number_format($client->debts->sum(function ($debt) { return $debt->payments->sum('amount'); }), 2) }}
    </p>
    <p><strong>Saldo Total Pendiente:</strong>
        ${{ number_format($client->debts->sum('amount') - $client->debts->sum(function ($debt) { return $debt->payments->sum('amount'); }), 2) }}
    </p>
</div>
</body>
</html>
