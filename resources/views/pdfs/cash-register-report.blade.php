<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reporte Detallado de Caja</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 18px;
            margin-bottom: 0;
        }

        h2 {
            font-size: 16px;
            margin-top: 5px;
            margin-bottom: 15px;
            color: #555;
        }

        h3 {
            font-size: 14px;
            margin-top: 20px;
            margin-bottom: 10px;
            background-color: #f4f4f4;
            padding: 5px;
            border-radius: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
            font-weight: bold;
        }

        .summary-box {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .day-separator {
            page-break-before: always;
            margin-top: 20px;
        }

        .general-summary {
            background-color: #ffffff;
            padding: 10px;
            margin: 20px 0;
            border: 1px solid #505050;
            border-radius: 4px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-success {
            color: #28a745;
            font-weight: bold;
        }

        .text-danger {
            color: #dc3545;
            font-weight: bold;
        }

        .text-warning {
            color: #ffc107;
        }

        .text-paid {
            color: #28a745;
            font-weight: bold;
        }

        .text-pending {
            color: #dc3545;
            font-weight: bold;
        }

        .text-partial {
            color: #007bff;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #777;
        }
    </style>
</head>

<body>
<div class="header">
    <h1>Reporte Detallado de Caja</h1>
    <h2>Período: {{ $start_date }} - {{ $end_date }}</h2>
    <p>Generado el: {{ \Carbon\Carbon::now()->format('d-m-Y H:i:s') }}</p>
</div>

<div class="general-summary">
    <h3>Resumen General del Período</h3>
    <table>
        <tr>
            <th>Total Entregas</th>
            <th>Total Facturado</th>
            <th>Total Cobrado</th>
            <th>Total Gastos</th>
            <th>Balance Final</th>
        </tr>
        <tr>
            <td class="text-center">{{ $general_summary['total_delivered'] }}</td>
            <td class="text-right">${{ number_format($general_summary['total_invoiced'], 2) }}</td>
            <td class="text-right">${{ number_format($general_summary['total_collected'], 2) }}</td>
            <td class="text-right">${{ number_format($general_summary['total_expenses'], 2) }}</td>
            <td class="text-right @if($general_summary['balance'] >= 0) text-success @else text-danger @endif">
                ${{ number_format($general_summary['balance'], 2) }}
            </td>
        </tr>
    </table>
</div>

@foreach($period_data as $index => $data)
    <div class="@if($index > 0) day-separator @endif">
        <h3>{{ $period_labels[$index] }}</h3>

        <div class="summary-box">
            <table>
                <tr>
                    <th>Entregas</th>
                    <th>Facturado</th>
                    <th>Cobrado</th>
                    <th>Gastos</th>
                    <th>Balance</th>
                </tr>
                <tr>
                    <td class="text-center">{{ $data['summary']['total_delivered'] }}</td>
                    <td class="text-right">${{ number_format($data['summary']['total_invoiced'], 2) }}</td>
                    <td class="text-right">${{ number_format($data['summary']['total_collected'], 2) }}</td>
                    <td class="text-right">${{ number_format($data['summary']['total_expenses'], 2) }}</td>
                    <td class="text-right @if($data['summary']['balance'] >= 0) text-success @else text-danger @endif">
                        ${{ number_format($data['summary']['balance'], 2) }}
                    </td>
                </tr>
            </table>
        </div>

        @if(isset($data['deliveriesByStatus']['delivered']) && count($data['deliveriesByStatus']['delivered']) > 0)
            <h3>Entregas Realizadas ({{ count($data['deliveriesByStatus']['delivered']) }})</h3>
            <table>
                <thead>
                <tr>
                    <th>Número</th>
                    <th>Cliente</th>
                    <th>Repartidor</th>
                    <th>Servicio</th>
                    <th>Monto</th>
                    <th>Pagado</th>
                    <th>Pendiente</th>
                    <th>Estado Pago</th>
                </tr>
                </thead>
                <tbody>
                @foreach($data['deliveriesByStatus']['delivered'] as $delivery)
                    @php
                        $statusClass = match ($delivery['payment_status']) {
                            'paid' => 'text-paid',
                            'pending' => 'text-pending',
                            'partial_paid' => 'text-partial',
                            default => ''
                        };
                    @endphp
                    <tr>
                        <td>{{ $delivery['number'] }}</td>
                        <td>{{ $delivery['client'] === 'N/A' ? 'Sin cliente' : $delivery['client'] }}</td>
                        <td>{{ $delivery['courier'] === 'N/A' ? 'Sin repartidor' : $delivery['courier'] }}</td>
                        <td>{{ $delivery['service'] }}</td>
                        <td class="text-right">
                            ${{ number_format($delivery['total_amount'] ?? $delivery['amount'] ?? 0, 2) }}</td>
                        <td class="text-right text-paid">${{ number_format($delivery['paid_amount'] ?? 0, 2) }}</td>
                        <td class="text-right @if(($delivery['pending_amount'] ?? 0) > 0) text-pending @endif">
                            ${{ number_format($delivery['pending_amount'] ?? 0, 2) }}
                        </td>
                        <td class="{{ $statusClass }}">
                            {{ \App\Helpers\DeliveryStatusTranslator::toSpanish($delivery['payment_status']) }}
                        </td>
                    </tr>
                    @if(isset($delivery['payment_details']) && count($delivery['payment_details']) > 0)
                        <tr>
                            <td colspan="8" style="padding: 0;">
                                <table style="margin-bottom: 0; border-top: none;">
                                    <tr>
                                        <td colspan="8"
                                            style="background-color: #f9f9f9; font-weight: bold; text-align: center;">
                                            Detalle de Pagos
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Monto</th>
                                        <th>Método</th>
                                        <th colspan="4">Notas</th>
                                        <th>Estado</th>
                                    </tr>
                                    @foreach($delivery['payment_details'] as $paymentDetail)
                                        <tr>
                                            <td>{{ $paymentDetail['date'] }}</td>
                                            <td class="text-right text-paid">
                                                ${{ number_format($paymentDetail['amount'], 2) }}</td>
                                            <td>{{ $paymentDetail['payment_method'] }}</td>
                                            <td colspan="4">{{ $paymentDetail['notes'] }}</td>
                                            <td class="text-paid">Registrado</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        @else
            <p>No hay entregas realizadas en este período</p>
        @endif

        @if(isset($data['deliveriesByStatus']['canceled']) && count($data['deliveriesByStatus']['canceled']) > 0)
            <h3>Entregas Canceladas ({{ count($data['deliveriesByStatus']['canceled']) }})</h3>
            <table>
                <thead>
                <tr>
                    <th>Número</th>
                    <th>Cliente</th>
                    <th>Repartidor</th>
                    <th>Monto</th>
                    <th>Motivo de Cancelación</th>
                </tr>
                </thead>
                <tbody>
                @foreach($data['deliveriesByStatus']['canceled'] as $delivery)
                    <tr>
                        <td>{{ $delivery['number'] }}</td>
                        <td>{{ $delivery['client'] === 'N/A' ? 'Sin cliente' : $delivery['client'] }}</td>
                        <td>{{ $delivery['courier'] === 'N/A' ? 'Sin repartidor' : $delivery['courier'] }}</td>
                        <td class="text-right">
                            ${{ number_format($delivery['total_amount'] ?? $delivery['amount'] ?? 0, 2) }}</td>
                        <td>{{ $delivery['cancellation_notes'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <p>No hay entregas canceladas en este período</p>
        @endif

        @if(isset($data['previousDayPayments']) && count($data['previousDayPayments']) > 0)
            <h3>Pagos Recibidos de Entregas Anteriores</h3>
            <table>
                <thead>
                <tr>
                    <th>numero</th>
                    <th>Fecha Entrega</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Pagado</th>
                    <th>Pendiente</th>
                    <th>Detalle de Pagos</th>
                </tr>
                </thead>
                <tbody>
                @foreach($data['previousDayPayments'] as $payment)
                    <tr>
                        <td>{{ $payment['number'] }}</td>
                        <td>{{ $payment['date'] }}</td>
                        <td>{{ $payment['client'] }}</td>
                        <td class="text-right">${{ number_format($payment['total_amount'], 2) }}</td>
                        <td class="text-right text-paid">${{ number_format($payment['paid_amount'], 2) }}</td>
                        <td class="text-right @if($payment['pending_amount'] > 0) text-pending @endif">
                            ${{ number_format($payment['pending_amount'], 2) }}
                        </td>
                        <td>
                            <div style="font-size: 11px; padding-left: 5px;">
                                @foreach($payment['payment_details'] as $detail)
                                    <p style="margin: 0;">{{ $detail['date'] }}:
                                        ${{ number_format($detail['amount'], 2) }}
                                        ({{ $detail['payment_method'] }})</p>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <p>No hay pagos de entregas anteriores en este período</p>
        @endif
    </div>
@endforeach
</body>

</html>
