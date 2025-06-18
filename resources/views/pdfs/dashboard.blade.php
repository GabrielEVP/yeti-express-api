<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
        }

        .summary-table {
            margin-bottom: 20px;
        }

        .section-title {
            background-color: #f8f8f8;
            padding: 5px;
            margin-top: 20px;
            border-bottom: 2px solid #333;
        }

        .subsection {
            margin-top: 15px;
            margin-bottom: 25px;
        }

        .cash-register-box {
            border: 2px solid #333;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 30px;
            background-color: #ffffff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .cash-register-title {
            background-color: #f0f0f0;
            padding: 8px;
            margin: -15px -15px 15px -15px;
            border-bottom: 2px solid #333;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
        }

        .page-break {
            page-break-before: always;
        }

    </style>
</head>

<body>

    <h2>Reporte de Caja</h2>
    <p>
        <strong>Período:</strong> {{ ucfirst($period) }}<br>
        <strong>Fecha:</strong> {{ $date }}<br>
        @if(isset($start_date) && isset($end_date))
            <strong>Desde:</strong> {{ $start_date }} <strong>Hasta:</strong> {{ $end_date }}
        @endif
    </p>

    @foreach($period_labels as $index => $label)
    <div class="cash-register-box">
        <div class="cash-register-title">{{ $label }}</div>

        <h3 class="section-title">Resumen General</h3>
        <table class="summary-table">
            <tr>
                <th>Total Entregados</th>
                <td>{{ $period_data[$index]['summary']['total_delivered'] }}</td>
            </tr>
            <tr>
                <th>Total Facturado</th>
                <td>${{ number_format($period_data[$index]['summary']['total_invoiced'], 2) }}</td>
            </tr>
            <tr>
                <th>Total Cobrado</th>
                <td>${{ number_format($period_data[$index]['summary']['total_collected'], 2) }}</td>
            </tr>
            <tr>
                <th>Total Gastos</th>
                <td>${{ number_format($period_data[$index]['summary']['total_expenses'], 2) }}</td>
            </tr>
            <tr>
                <th>Balance</th>
                <td><strong>${{ number_format($period_data[$index]['summary']['balance'], 2) }}</strong></td>
            </tr>
        </table>

    <!-- Deliveries Entregados -->
    <h3 class="section-title">Deliveries Entregados ({{ count($period_data[$index]['deliveriesByStatus']['delivered']) }})</h3>
    @if(count($period_data[$index]['deliveriesByStatus']['delivered']) > 0)
    <div class="subsection">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Courier</th>
                    <th>Servicio</th>
                    <th>Monto</th>
                    <th>Estado de Pago</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($period_data[$index]['deliveriesByStatus']['delivered'] as $delivery)
                    <tr>
                        <td>{{ $delivery['number'] }}</td>
                        <td>{{ $delivery['date'] }}</td>
                        <td>{{ $delivery['client'] }}</td>
                        <td>{{ $delivery['courier'] }}</td>
                        <td>{{ $delivery['service'] }}</td>
                        <td>${{ number_format($delivery['amount'], 2) }}</td>
                        <td>{{ ucfirst($delivery['payment_status']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p>No hay deliveries entregados en este período.</p>
    @endif

    <!-- Deliveries Cancelados -->
    <h3 class="section-title">Deliveries Cancelados ({{ count($period_data[$index]['deliveriesByStatus']['canceled']) }})</h3>
    @if(count($period_data[$index]['deliveriesByStatus']['canceled']) > 0)
    <div class="subsection">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Courier</th>
                    <th>Servicio</th>
                    <th>Monto</th>
                    <th>Notas de Cancelación</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($period_data[$index]['deliveriesByStatus']['canceled'] as $delivery)
                    <tr>
                        <td>{{ $delivery['number'] }}</td>
                        <td>{{ $delivery['date'] }}</td>
                        <td>{{ $delivery['client'] }}</td>
                        <td>{{ $delivery['courier'] }}</td>
                        <td>{{ $delivery['service'] }}</td>
                        <td>${{ number_format($delivery['amount'], 2) }}</td>
                        <td>{{ $delivery['cancellation_notes'] ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p>No hay deliveries cancelados en este período.</p>
    @endif

    <div class="page-break"></div>

    <!-- Deliveries Cobrados -->
    <h3 class="section-title">Deliveries Cobrados ({{ count($deliveriesByStatus['collected']) }})</h3>
    @if(count($deliveriesByStatus['collected']) > 0)
    <div class="subsection">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Courier</th>
                    <th>Servicio</th>
                    <th>Monto</th>
                    <th>Tipo de Pago</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($deliveriesByStatus['collected'] as $delivery)
                    <tr>
                        <td>{{ $delivery['number'] }}</td>
                        <td>{{ $delivery['date'] }}</td>
                        <td>{{ $delivery['client'] }}</td>
                        <td>{{ $delivery['courier'] }}</td>
                        <td>{{ $delivery['service'] }}</td>
                        <td>${{ number_format($delivery['amount'], 2) }}</td>
                        <td>{{ ucfirst($delivery['payment_type']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p>No hay deliveries cobrados en este período.</p>
    @endif

    <!-- Deliveries No Cobrados -->
    <h3 class="section-title">Deliveries No Cobrados ({{ count($deliveriesByStatus['uncollected']) }})</h3>
    @if(count($deliveriesByStatus['uncollected']) > 0)
    <div class="subsection">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Courier</th>
                    <th>Servicio</th>
                    <th>Monto</th>
                    <th>Estado de Pago</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($deliveriesByStatus['uncollected'] as $delivery)
                    <tr>
                        <td>{{ $delivery['number'] }}</td>
                        <td>{{ $delivery['date'] }}</td>
                        <td>{{ $delivery['client'] }}</td>
                        <td>{{ $delivery['courier'] }}</td>
                        <td>{{ $delivery['service'] }}</td>
                        <td>${{ number_format($delivery['amount'], 2) }}</td>
                        <td>{{ ucfirst($delivery['payment_status']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <p>No hay deliveries pendientes de cobro en este período.</p>
    @endif

    <div class="page-break"></div>

    <!-- Resumen por Repartidor -->
    <h3 class="section-title">Resumen por Repartidor</h3>
    @if(count($period_data[$index]['courierSummary']) > 0)
    <div class="subsection">
        <table>
            <thead>
                <tr>
                    <th>Repartidor</th>
                    <th>Total Deliveries</th>
                    <th>Entregados</th>
                    <th>Monto Entregados</th>
                    <th>Cancelados</th>
                    <th>Monto Cancelados</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($period_data[$index]['courierSummary'] as $summary)
                    <tr>
                        <td>{{ $summary['courier'] }}</td>
                        <td>{{ $summary['total_deliveries'] }}</td>
                        <td>{{ $summary['delivered_count'] }}</td>
                        <td>${{ number_format($summary['delivered_amount'], 2) }}</td>
                        <td>{{ $summary['canceled_count'] }}</td>
                        <td>${{ number_format($summary['canceled_amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

                    @foreach ($period_data[$index]['courierSummary'] as $summary)
            <div class="subsection">
                <h4>Detalle de {{ $summary['courier'] }}</h4>

                @if(count($summary['deliveries']['delivered']) > 0)
                <h5>Entregados ({{ count($summary['deliveries']['delivered']) }})</h5>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Monto</th>
                            <th>Estado de Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($summary['deliveries']['delivered'] as $delivery)
                            <tr>
                                <td>{{ $delivery['number'] }}</td>
                                <td>{{ $delivery['date'] }}</td>
                                <td>{{ $delivery['client'] }}</td>
                                <td>${{ number_format($delivery['amount'], 2) }}</td>
                                <td>{{ ucfirst($delivery['payment_status']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif

                @if(count($summary['deliveries']['canceled']) > 0)
                <h5>Cancelados ({{ count($summary['deliveries']['canceled']) }})</h5>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Monto</th>
                            <th>Motivo de Cancelación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($summary['deliveries']['canceled'] as $delivery)
                            <tr>
                                <td>{{ $delivery['number'] }}</td>
                                <td>{{ $delivery['date'] }}</td>
                                <td>{{ $delivery['client'] }}</td>
                                <td>${{ number_format($delivery['amount'], 2) }}</td>
                                <td>{{ $delivery['cancellation_notes'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        @endforeach
    </div>
    @else
    <p>No hay información de repartidores para este período.</p>
    @endif

    </div> <!-- Cierre de cash-register-box -->

    @if(!$loop->last)
    <div class="page-break"></div>
    @endif

    @endforeach

    </div> <!-- Cierre de cash-register-box -->

    @if(!$loop->last)
    <div class="page-break"></div>
    @endif

    @endforeach

    <!-- Resumen de Deudas por Cliente -->
    <h3 class="section-title">Resumen de Deudas por Cliente</h3>
    @if(count($clientDebtSummary) > 0)
    <div class="subsection">
        @foreach ($clientDebtSummary as $clientSummary)
            <h4>{{ $clientSummary['client'] }} - Deuda Total: ${{ number_format($clientSummary['total_debt'], 2) }}</h4>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Monto Total</th>
                        <th>Monto Pagado</th>
                        <th>Monto Pendiente</th>
                        <th>Estado de Pago</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($clientSummary['deliveries'] as $delivery)
                        <tr>
                            <td>{{ $delivery['number'] }}</td>
                            <td>{{ $delivery['date'] }}</td>
                            <td>${{ number_format($delivery['amount'], 2) }}</td>
                            <td>${{ number_format($delivery['paid_amount'], 2) }}</td>
                            <td>${{ number_format($delivery['pending_amount'], 2) }}</td>
                            <td>{{ ucfirst($delivery['payment_status']) }}</td>
                        </tr>
                        @if(count($delivery['payment_details']) > 0)
                            <tr>
                                <td colspan="6">
                                    <strong>Historial de pagos:</strong>
                                    <ul style="margin: 0; padding-left: 20px;">
                                        @foreach ($delivery['payment_details'] as $payment)
                                            <li>
                                                {{ $payment['date'] }} - ${{ number_format($payment['amount'], 2) }}
                                                ({{ ucfirst($payment['payment_method']) }})
                                                @if($payment['notes'])
                                                - {{ $payment['notes'] }}
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        @endforeach
    </div>
    @else
    <p>No hay clientes con deudas pendientes en este período.</p>
    @endif

    <!-- Resumen de Pagos por Cliente -->
    <h3 class="section-title">Resumen de Pagos por Cliente</h3>
    @if(isset($clientPaymentSummary) && count($clientPaymentSummary) > 0)
    <div class="subsection">
        @foreach ($clientPaymentSummary as $clientSummary)
            <h4>{{ $clientSummary['client'] }} - Total Pagado: ${{ number_format($clientSummary['total_paid'], 2) }}</h4>

            @if(count($clientSummary['full_payments']) > 0)
                <h5>Pagos Completos ({{ $clientSummary['full_payments_count'] }}) - ${{ number_format($clientSummary['full_payments_total'], 2) }}</h5>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fecha Delivery</th>
                            <th>Fecha Pago</th>
                            <th>Monto</th>
                            <th>Tipo de Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clientSummary['full_payments'] as $payment)
                            <tr>
                                <td>{{ $payment['number'] }}</td>
                                <td>{{ $payment['date'] }}</td>
                                <td>{{ $payment['payment_date'] }}</td>
                                <td>${{ number_format($payment['amount'], 2) }}</td>
                                <td>{{ ucfirst($payment['payment_type']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if(count($clientSummary['partial_payments']) > 0)
                <h5>Pagos Parciales ({{ $clientSummary['partial_payments_count'] }}) - ${{ number_format($clientSummary['partial_payments_total'], 2) }}</h5>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Fecha Delivery</th>
                            <th>Fecha Pago</th>
                            <th>Monto Pagado</th>
                            <th>Monto Total</th>
                            <th>Método</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clientSummary['partial_payments'] as $payment)
                            <tr>
                                <td>{{ $payment['number'] }}</td>
                                <td>{{ $payment['date'] }}</td>
                                <td>{{ $payment['payment_date'] }}</td>
                                <td>${{ number_format($payment['amount'], 2) }}</td>
                                <td>${{ number_format($payment['delivery_amount'], 2) }}</td>
                                <td>{{ ucfirst($payment['payment_method']) }}</td>
                                <td>{{ $payment['notes'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach
    </div>
    @else
    <p>No hay pagos de clientes registrados en este período.</p>
    @endif

</body>

</html>
