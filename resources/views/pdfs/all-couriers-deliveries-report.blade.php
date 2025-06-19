<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reporte General de Entregas de Repartidores</title>
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
            font-weight: bold;
            margin-bottom: 15px;
            padding: 5px;
            background-color: #f0f0f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f5f5f5;
        }

        .courier-section {
            margin-top: 30px;
            margin-bottom: 20px;
            padding-top: 10px;
            border-top: 2px solid #333;
        }

        .courier-info {
            margin-bottom: 10px;
            background-color: #eaeaea;
            padding: 5px;
        }

        .summary {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9f9f9;
        }

        .status-pending {
            color: #ff0000;
        }

        .status-in-transit {
            color: #ffa500;
        }

        .status-delivered {
            color: #008000;
        }

        .status-cancelled {
            color: #808080;
        }

        .totals {
            margin-top: 30px;
            padding: 15px;
            background-color: #e6e6e6;
            border: 1px solid #999;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Reporte General de Entregas de Repartidores</h1>
        <p>Fecha de generación: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="date-range">
        <p>Periodo del reporte: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
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

            @if($courier->deliveries->count() > 0)
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
                                <td>{{ $delivery->date->format('d/m/Y') }}</td>
                                <td>{{ $delivery->client->legal_name }}</td>
                                <td>{{ $delivery->service->name }}</td>
                                <td>${{ number_format($delivery->amount, 2) }}</td>
                                <td class="status-{{ $delivery->status }}">
                                    @if($delivery->status === 'pending')
                                        Pendiente
                                    @elseif($delivery->status === 'in_transit')
                                        En Tránsito
                                    @elseif($delivery->status === 'delivered')
                                        Entregado
                                    @else
                                        Cancelado
                                    @endif
                                </td>
                                <td>{{ $delivery->receipt->address }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="summary">
                    <h3>Resumen del Repartidor</h3>
                    <p><strong>Entregas Pendientes:</strong> {{ $courier->deliveries->where('status', 'pending')->count() }}</p>
                    <p><strong>Entregas en Tránsito:</strong> {{ $courier->deliveries->where('status', 'in_transit')->count() }}</p>
                    <p><strong>Entregas Completadas:</strong> {{ $courier->deliveries->where('status', 'delivered')->count() }}</p>
                    <p><strong>Entregas Canceladas:</strong> {{ $courier->deliveries->where('status', 'cancelled')->count() }}</p>
                </div>
            @else
                <p>No hay entregas para este repartidor en el período seleccionado.</p>
            @endif
        </div>
    @endforeach

</body>

</html>
