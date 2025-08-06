<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Reporte General de Entregas de Repartidores</title>
    <style>
        {!! file_get_contents(base_path('app/Courier/Resources/css/CourierReport.css')) !!}
    </style>
</head>

<body>
<div class="header">
    <h1>Reporte General de Entregas de Repartidores</h1>
    <p>Fecha de generación: {{ now()->format('d/m/Y H:i:s') }}</p>
</div>

<div class="date-range">
    <p>Periodo del reporte: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}
        al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
</div>

@php
    $totalDeliveries = 0;
    $totalPending = 0;
    $totalInTransit = 0;
    $totalDelivered = 0;
    $totalCancelled = 0;
    $totalAmount = 0.0;

    foreach ($couriers as $courier) {
        $deliveries = $courier['deliveries'] ?? [];

        $totalDeliveries += count($deliveries);
        foreach ($deliveries as $delivery) {
            $status = $delivery['status']->value ?? $delivery['status'];
            switch ($status) {
                case 'pending':
                    $totalPending++;
                    break;
                case 'in_transit':
                    $totalInTransit++;
                    break;
                case 'delivered':
                    $totalDelivered++;
                    $totalAmount += $delivery['amount']; 
                    break;
                case 'cancelled':
                default:
                    $totalCancelled++;
                    break;
            }
        }
    }
@endphp

<div class="totals">
    <h2>Resumen General</h2>
    <p><strong>Total de Repartidores:</strong> {{ count($couriers) }}</p>
    <p><strong>Total de Entregas:</strong> {{ $totalDeliveries }}</p>
    <p><strong>Entregas Pendientes:</strong> {{ $totalPending }}</p>
    <p><strong>Entregas en Tránsito:</strong> {{ $totalInTransit }}</p>
    <p><strong>Entregas Completadas:</strong> {{ $totalDelivered }}</p>
    <p><strong>Entregas Canceladas:</strong> {{ $totalCancelled }}</p>
    <p><strong>Total de Montos:</strong> ${{ number_format($totalAmount, 2, ',', '.') }}</p>
</div>

@foreach($couriers as $index => $courier)
    @if($index > 0)
        <div class="page-break"></div>
    @endif

    <div class="courier-section">
        <div class="courier-info">
            <h2>Repartidor: {{ $courier['full_name'] }}</h2>
            <p><strong>Teléfono:</strong> {{ $courier['phone'] }}</p>
            <p><strong>Total de Entregas:</strong> {{ count($courier['deliveries']) }}</p>
            <p><strong>Monto Total:</strong>
                @php
                    $courierDeliveredAmount = 0;
                    foreach($courier['deliveries'] as $delivery) {
                        if(($delivery['status']->value ?? $delivery['status']) === 'delivered') {
                            $courierDeliveredAmount += $delivery['amount'];
                        }
                    }
                @endphp
                ${{ number_format($courierDeliveredAmount, 2, ',', '.') }}</p>
        </div>

        @if(count($courier['deliveries']) > 0)
            <table>
                <thead>
                <tr>
                    <th>Número</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Notas de Cancelación</th>
                </tr>
                </thead>
                <tbody>
                @foreach($courier['deliveries'] as $delivery)
                    <tr>
                        <td>{{ $delivery['number'] }}</td>
                        <td>{{ $delivery['date'] }}</td>
                        <td style="{{ isset($delivery['is_anonymous_client']) && $delivery['is_anonymous_client'] ? 'background-color: #FFFF00;' : '' }}">{{ $delivery['client_name'] }}</td>
                        <td>${{ number_format($delivery['amount'], 2, ',', '.') }}</td>
                        <td class="status-{{ $delivery['status']->value ?? $delivery['status'] }}">
                            @switch($delivery['status']->value ?? $delivery['status'])
                                @case('pending') Pendiente @break
                                @case('in_transit') En Tránsito @break
                                @case('delivered') Entregado @break
                                @default Cancelado
                            @endswitch
                        </td>
                        <td>{{ $delivery['cancellation_notes'] ?? '-' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div class="summary">
                <h3>Resumen del Repartidor</h3>
                @php
                    $statusCount = ['pending' => 0, 'in_transit' => 0, 'delivered' => 0, 'cancelled' => 0];
                    foreach ($courier['deliveries'] as $delivery) {
                        $status = $delivery['status']->value ?? $delivery['status'] ?? 'cancelled';
                        $statusCount[$status] = ($statusCount[$status] ?? 0) + 1;
                    }
                @endphp
                <p><strong>Entregas Pendientes:</strong> {{ $statusCount['pending'] }}</p>
                <p><strong>Entregas en Tránsito:</strong> {{ $statusCount['in_transit'] }}</p>
                <p><strong>Entregas Completadas:</strong> {{ $statusCount['delivered'] }}</p>
                <p><strong>Entregas Canceladas:</strong> {{ $statusCount['cancelled'] }}</p>
            </div>
        @else
            <p>No hay entregas para este repartidor en el período seleccionado.</p>
        @endif
    </div>
@endforeach

</body>
</html>
