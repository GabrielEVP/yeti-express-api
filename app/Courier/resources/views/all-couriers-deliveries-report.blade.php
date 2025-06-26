<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Reporte General de Entregas de Repartidores</title>
    <style>
        {!! file_get_contents(base_path('app/Courier/resources/css/CourierReport.css')) !!}
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

<div class="totals">
    <h2>Resumen General</h2>
    @php
        $totalDeliveries = 0;
        $totalPending = 0;
        $totalInTransit = 0;
        $totalDelivered = 0;
        $totalCancelled = 0;
        $totalAmount = 0;

        foreach ($couriers as $courier) {
            $totalDeliveries += $courier->deliveries->count();
            $totalPending += $courier->deliveries->where('status', 'pending')->count();
            $totalInTransit += $courier->deliveries->where('status', 'in_transit')->count();
            $totalDelivered += $courier->deliveries->where('status', 'delivered')->count();
            $totalCancelled += $courier->deliveries->where('status', 'cancelled')->count();
            $totalAmount += $courier->deliveries->sum('amount');
        }
    @endphp

    <p><strong>Total de Repartidores:</strong> {{ $couriers->count() }}</p>
    <p><strong>Total de Entregas:</strong> {{ $totalDeliveries }}</p>
    <p><strong>Entregas Pendientes:</strong> {{ $totalPending }}</p>
    <p><strong>Entregas en Tránsito:</strong> {{ $totalInTransit }}</p>
    <p><strong>Entregas Completadas:</strong> {{ $totalDelivered }}</p>
    <p><strong>Entregas Canceladas:</strong> {{ $totalCancelled }}</p>
    <p><strong>Total de Montos:</strong> ${{ number_format($totalAmount, 2) }}</p>
</div>

@foreach($couriers as $courier)
    @if(!$loop->first)
        <div class="page-break"></div>
    @endif

    <div class="courier-section">
        <div class="courier-info">
            <h2>Repartidor: {{ $courier->first_name }} {{ $courier->last_name }}</h2>
            <p><strong>Teléfono:</strong> {{ $courier->phone }}</p>
            <p><strong>Total de Entregas:</strong> {{ $courier->deliveries->count() }}</p>
            <p><strong>Monto Total:</strong> ${{ number_format($courier->deliveries->sum('amount'), 2) }}</p>
        </div>

        @if($courier->deliveries->isNotEmpty())
            <table>
                <thead>
                <tr>
                    <th>Número</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Servicio</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Dirección de Entrega</th>
                </tr>
                </thead>
                <tbody>
                @foreach($courier->deliveries as $delivery)
                    <tr>
                        <td>{{ $delivery->number }}</td>
                        <td>{{ \Carbon\Carbon::parse($delivery->date)->format('d/m/Y') }}</td>
                        <td>{{ $delivery->client->legal_name }}</td>
                        <td>{{ $delivery->service->name }}</td>
                        <td>${{ number_format($delivery->amount, 2) }}</td>
                        <td class="status-{{ $delivery->status }}">
                            @switch($delivery->status)
                                @case('pending') Pendiente @break
                                @case('in_transit') En Tránsito @break
                                @case('delivered') Entregado @break
                                @default Cancelado
                            @endswitch
                        </td>
                        <td>{{ $delivery->receipt->address }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div class="summary">
                <h3>Resumen del Repartidor</h3>
                <p><strong>Entregas Pendientes:</strong> {{ $courier->deliveries->where('status', 'pending')->count() }}
                </p>
                <p><strong>Entregas en
                        Tránsito:</strong> {{ $courier->deliveries->where('status', 'in_transit')->count() }}</p>
                <p><strong>Entregas
                        Completadas:</strong> {{ $courier->deliveries->where('status', 'delivered')->count() }}</p>
                <p><strong>Entregas
                        Canceladas:</strong> {{ $courier->deliveries->where('status', 'cancelled')->count() }}</p>
            </div>
        @else
            <p>No hay entregas para este repartidor en el período seleccionado.</p>
        @endif
    </div>
@endforeach

</body>
</html>
