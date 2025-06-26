<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de Entregas del Repartidor</title>
    <style>
        {!! file_get_contents(base_path('app/Courier/resources/css/CourierReport.css')) !!}
    </style>
</head>
<body>

<div class="header">
    <h1>Reporte de Entregas del Repartidor</h1>
    <p>Generado el {{ now()->format('d/m/Y H:i:s') }}</p>
</div>

<div class="courier-info">
    <h2>Información del Repartidor</h2>
    <p><strong>Nombre:</strong> {{ $courier->first_name }} {{ $courier->last_name }}</p>
    <p><strong>Teléfono:</strong> {{ $courier->phone }}</p>
</div>

@if($startDate && $endDate)
    <div class="date-range">
        Reporte desde {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}
        hasta {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
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
            <td>{{ $delivery['number'] }}</td>
            <td>{{ $delivery['date'] }}</td>
            <td>{{ is_array($delivery['client']) ? $delivery['client']['name'] : $delivery['client'] }}</td>
            <td>{{ is_array($delivery['service']) ? $delivery['service']['name'] : $delivery['service'] }}</td>
            <td>${{ number_format($delivery['amount'], 2, ',', '.') }}</td>
            <td class="status-{{ $delivery['status'] }}">
                @switch($delivery['status'])
                    @case('pending')     Pendiente @break
                    @case('in_transit')  En Tránsito @break
                    @case('delivered')   Entregado @break
                    @default             Cancelado
                @endswitch
            </td>
            <td>{{ is_array($delivery['receipt']) ? $delivery['receipt']['number'] : $delivery['receipt'] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="summary">
    <h3>Resumen</h3>
    <p><strong>Total de Entregas:</strong> {{ $deliveries->count() }}</p>
    <p><strong>Pendientes:</strong> {{ $deliveries->where('status', 'pending')->count() }}</p>
    <p><strong>En Tránsito:</strong> {{ $deliveries->where('status', 'in_transit')->count() }}</p>
    <p><strong>Completadas:</strong> {{ $deliveries->where('status', 'delivered')->count() }}</p>
    <p><strong>Canceladas:</strong> {{ $deliveries->where('status', 'cancelled')->count() }}</p>
    <p><strong>Monto Total:</strong>
        ${{ number_format($deliveries->sum(function($delivery) { return (float) str_replace([',', '.'], ['.', ''], $delivery['amount']); }), 2, ',', '.') }}
    </p>
</div>

</body>
</html>
