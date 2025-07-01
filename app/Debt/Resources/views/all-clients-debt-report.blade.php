<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Deudas de Todos los Clientes</title>
    <link rel="stylesheet" href="{{ public_path('css/debt-report.css') }}">
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

@if($clients instanceof \App\Debt\DTO\ClientsDebtsCollectionDTO)
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

        @foreach($clients instanceof \App\Debt\DTO\ClientsDebtsCollectionDTO ? $clients->getClients() : $clients as $client)
            @php
                // Check if we're working with a DTO or Model
                $isDTO = !is_object($client) || !method_exists($client, 'getAttributes');

                if ($isDTO) {
                    // Handle DTO structure
                    $clientTotalDebt = collect($client->debts)->sum('amount');
                    $clientTotalPaid = collect($client->debts)->sum(function ($debt) {
                        return collect($debt['payments'] ?? [])->sum('amount');
                    });
                } else {
                    // Handle Eloquent model
                    $clientTotalDebt = $client->debts->sum('amount');
                    $clientTotalPaid = $client->debts->sum(function ($debt) {
                        return $debt->payments->sum('amount');
                    });
                $clientTotalPending = $clientTotalDebt - $clientTotalPaid;

                }

                $clientTotalPending = $clientTotalDebt - $clientTotalPaid;
                $grandTotalDebt += $clientTotalDebt;
                $grandTotalPaid += $clientTotalPaid;
                $grandTotalPending += $clientTotalPending;
            @endphp

            <div class="client-section">
                <div class="client-header">
                    <h2>{{ $isDTO ? $client->legalName : $client->legal_name }}</h2>
                    <p><strong>Tipo:</strong> {{ $client->type ?? 'N/A' }} | <strong>Número de
                            Registro:</strong> {{ $isDTO ? $client->registrationNumber : $client->registration_number }}
                    </p>
                </div>

                @if(($isDTO && empty($client->debts)) || (!$isDTO && $client->debts->isEmpty()))
                    <div class="no-records">
                        <p>Este cliente no tiene deudas registradas en el período seleccionado.</p>
                    </div>
                @else
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
                        @foreach($isDTO ? $client->debts : $client->debts as $debt)
                            @php
                                $debtIsArray = is_array($debt);
                            @endphp
                            <tr>
                                <td>{{ $debtIsArray ? $debt['delivery']['number'] : $debt->delivery->number }}</td>
                                <td>{{ $debtIsArray ? date('d/m/Y', strtotime($debt['delivery']['date'])) : $debt->delivery->date->format('d/m/Y') }}</td>
                                <td>${{ number_format($debtIsArray ? $debt['amount'] : $debt->amount, 2) }}</td>
                                <td class="status-{{ $debtIsArray ? $debt['status'] : $debt->status }}">
                                    @if($debt->status === 'pending')
                                        Pendiente
                                    @elseif($debt->status === 'partial_paid')
                                        Parcial
                                    @else
                                        Pagado
                                    @endif
                                </td>
                                <td>
                                    @if($debtIsArray)
                                        @if(empty($debt['payments']))
                                            Sin pagos
                                        @else
                                            @foreach($debt['payments'] as $payment)
                                                <div>
                                                    {{ date('d/m/Y', strtotime($payment['date'])) }} -
                                                    ${{ number_format($payment['amount'], 2) }}
                                                </div>
                                            @endforeach
                                        @endif
                                    @else
                                        @if($debt->payments->isEmpty())
                                            Sin pagos
                                        @else
                                            @foreach($debt->payments as $payment)
                                                <div>
                                                    {{ $payment->date->format('d/m/Y') }} -
                                                    ${{ number_format($payment->amount, 2) }}
                                                </div>
                                            @endforeach
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if($debtIsArray)
                                        ${{ number_format($debt['amount'] - collect($debt['payments'] ?? [])->sum('amount'), 2) }}
                                    @else
                                        ${{ number_format($debt->amount - $debt->payments->sum('amount'), 2) }}
                                    @endif
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
@else
    <div class="empty-state">
        <h2>No hay deudas registradas</h2>
        <p>No se encontraron clientes con deudas en el período seleccionado.</p>
    </div>
@endif
</body>
</html>
