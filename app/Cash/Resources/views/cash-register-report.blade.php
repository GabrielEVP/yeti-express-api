<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reporte Detallado de Caja</title>
    <style>
        {!! file_get_contents(base_path('app/Cash/Resources/css/Cash.css')) !!}
    </style>
</head>

<body>
<div class="header">
    <h1>Reporte Detallado de Caja</h1>
    <h2>Período: {{ $start_date }} - {{ $end_date }}</h2>
    <p>Generado el: {{ \Carbon\Carbon::now()->format('d-m-Y H:i:s') }}</p>
</div>

@if(empty($period_data) || $general_summary['total_delivered'] == 0)
    <div class="summary-box"
         style="background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; text-align: center; padding: 30px; margin: 20px 0;">
        <h3 style="font-size: 16px;">No se encontraron datos</h3>
        <p>No hay movimientos de caja registrados en el período
            del {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }}
            al {{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}</p>
    </div>
@endif

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
                            {{ \App\Delivery\Helpers\PaymentStatusTranslator::toSpanish($delivery['payment_status']) }}
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
            <h3>Pagos Recibidos de Entregas</h3>
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
            <p>No hay pagos de entregas en este período</p>
        @endif
    </div>
@endforeach
</body>

</html>
