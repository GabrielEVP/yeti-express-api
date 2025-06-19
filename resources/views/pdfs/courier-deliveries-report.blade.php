<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reporte de Entregas del Repartidor</title>
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

        .courier-info {
            margin-bottom: 20px;
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
        }

        th {
            background-color: #f5f5f5;
        }

        .summary {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9f9f9;
        }

        .date-range {
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
            padding: 5px;
            background-color: #f0f0f0;
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
    </style>
</head>

<body>
    <div class="header">
        <h1>Reporte de Entregas del Repartidor</h1>
        <p>Fecha de generación: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="courier-info">
        <h2>Información del Repartidor</h2>
        <p><strong>Nombre:</strong> {{ $courier->first_name }} {{ $courier->last_name }}</p>
        <p><strong>Teléfono:</strong> {{ $courier->phone }}</p>
    </div>

    @if($startDate && $endDate)
    <div class="date-range">
        <p>Periodo del reporte: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
    </div>
    @endif

    <h2>Historial de Entregas</h2>
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
            @foreach($deliveries as $delivery)
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
        <h3>Resumen</h3>
        <p><strong>Total de Entregas:</strong> {{ $deliveries->count() }}</p>
        <p><strong>Entregas Pendientes:</strong> {{ $deliveries->where('status', 'pending')->count() }}</p>
        <p><strong>Entregas en Tránsito:</strong> {{ $deliveries->where('status', 'in_transit')->count() }}</p>
        <p><strong>Entregas Completadas:</strong> {{ $deliveries->where('status', 'delivered')->count() }}</p>
        <p><strong>Entregas Canceladas:</strong> {{ $deliveries->where('status', 'cancelled')->count() }}</p>
        <p><strong>Total de Montos:</strong> ${{ number_format($deliveries->sum('amount'), 2) }}</p>
    </div>
</body>

</html>
