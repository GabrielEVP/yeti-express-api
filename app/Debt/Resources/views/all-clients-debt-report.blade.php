<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reporte de Deudas de Todos los Clientes</title>
    <style>
        {!! file_get_contents(base_path('app/Debt/Resources/css/debt.css')) !!}
    </style>
</head>

<body>
    <div class="header">
        <h1>Reporte de Deudas de Todos los Clientes</h1>
        <p>Fecha de generación: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="date-range">
        <p>Período: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}
            - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
    </div>

    @if($clients->isEmpty())
        <div class="empty-state">
            <h2>No hay deudas registradas</h2>
            <p>No se encontraron clientes con deudas en el período seleccionado.</p>
        </div>
    @else
        @php
            $grandTotalDebt = 0;
            $grandTotalPaid = 0;
            $grandTotalPending = 0;
        @endphp

        @foreach($clients as $client)
            @php
                $clientTotalDebt = $client->debts->sum('amount');
                $clientTotalPaid = 0;
                foreach ($client->debts as $debt) {
                    $clientTotalPaid += $debt->payments->sum('amount');
                }
                $clientTotalPending = $clientTotalDebt - $clientTotalPaid;

                $grandTotalDebt += $clientTotalDebt;
                $grandTotalPaid += $clientTotalPaid;
                $grandTotalPending += $clientTotalPending;
            @endphp

            <div class="client-section">
                <div class="client-header">
                    <h2>{{ $client->legal_name }}</h2>
                    <p><strong>Número de Registro:</strong> {{ $client->registration_number }}</p>
                </div>

                @if($client->debts->isEmpty())
                    <div class="no-records">
                        <p>Este cliente no tiene deudas registradas en el período seleccionado.</p>
                    </div>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Entrega</th>
                                <th>Fecha</th>
                                <th>Destinatario</th>
                                <th>Dirección</th>
                                <th>Monto</th>
                                <th>Estado</th>
                                <th>Pagos</th>
                                <th>Pendiente</th>
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
                                    <td>{{ $debt->delivery->receipt->full_name ?? 'N/A' }}</td>
                                    <td>{{ $debt->delivery->receipt->address ?? 'N/A' }}</td>
                                    <td>${{ number_format($debt->amount, 2) }}</td>
                                    <td
                                        class="status-{{ str_replace('_', '-', $debt->status instanceof \App\Debt\Models\Status ? $debt->status->value : $debt->status) }}">
                                        {{ \App\Debt\Helpers\StatusTranslator::toSpanish($debt->status instanceof \App\Debt\Models\Status ? $debt->status : \App\Debt\Models\Status::from($debt->status)) }}
                                    </td>
                                    <td>
                                        @forelse($debt->payments as $payment)
                                            <div>
                                                {{ $payment->date->format('d/m/Y') }} -
                                                ${{ number_format($payment->amount, 2) }}
                                            </div>
                                        @empty
                                            Sin pagos
                                        @endforelse
                                    </td>
                                    <td>${{ number_format($pending, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="summary">
                        <p><strong>Total de Deudas:</strong> ${{ number_format($clientTotalDebt, 2) }}</p>
                        <p><strong>Total Pagado:</strong> ${{ number_format($clientTotalPaid, 2) }}</p>
                        <p><strong>Saldo Pendiente:</strong> ${{ number_format($clientTotalPending, 2) }}</p>
                    </div>
                @endif
            </div>
        @endforeach

        <div class="grand-total">
            <h3>Resumen General</h3>
            <p><strong>Total de Deudas:</strong> ${{ number_format($grandTotalDebt, 2) }}</p>
            <p><strong>Total Pagado:</strong> ${{ number_format($grandTotalPaid, 2) }}</p>
            <p><strong>Saldo Total Pendiente:</strong> ${{ number_format($grandTotalPending, 2) }}</p>
        </div>
    @endif
</body>

</html>