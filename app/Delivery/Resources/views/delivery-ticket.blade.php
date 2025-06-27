<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Ticket de Entrega</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.2;
            margin: 0;
            padding: 5px;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .info {
            margin-bottom: 5px;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .footer {
            text-align: center;
            margin-top: 10px;
        }

        .status {
            font-weight: bold;
        }

        .status-delivered {
            color: #008000;
        }

        .status-pending {
            color: #ff0000;
        }

        .status-in-transit {
            color: #ffa500;
        }

        .status-cancelled {
            color: #808080;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>Ticket de Entrega</h2>
        <p>Número: {{ $delivery->number }}</p>
        <p>Fecha: {{ $delivery->created_at->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="divider"></div>

    <div class="info">
        <p><strong>Cliente:</strong> {{ $delivery->client->legal_name }}</p>
        <p><strong>Dirección:</strong> {{ $delivery->pickup_address }}</p>
    </div>

    <div class="divider"></div>

    <div class="info">
        <p><strong>Servicio:</strong> {{ $delivery->service->name }}</p>
        <p><strong>Monto:</strong> ${{ number_format($delivery->amount, 2) }}</p>
        <p><strong>Tipo de Pago:</strong>
            @if($delivery->payment_type === 'full')
                Completo
            @else
                Parcial
            @endif
        </p>
        <p><strong>Estado de Pago:</strong>
            @if($delivery->payment_status === 'pending')
                Pendiente
            @elseif($delivery->payment_status === 'partial_paid')
                Parcialmente Pagado
            @else
                Pagado
            @endif
        </p>
    </div>

    <div class="divider"></div>

    <div class="info">
        <p><strong>Repartidor:</strong> {{ $delivery->courier->first_name }} {{ $delivery->courier->last_name }}</p>
        <p><strong>Estado:</strong>
            <span class="status status-{{ $delivery->status }}">
                @if($delivery->status === 'pending')
                    Pendiente
                @elseif($delivery->status === 'in_transit')
                    En Tránsito
                @elseif($delivery->status === 'delivered')
                    Entregado
                @else
                    Cancelado
                @endif
            </span>
        </p>
    </div>

    <div class="divider"></div>

    <div class="info">
        <p><strong>Recibe:</strong> {{ $delivery->receipt->full_name }}</p>
        <p><strong>Teléfono:</strong> {{ $delivery->receipt->phone }}</p>
        <p><strong>Dirección de Entrega:</strong> {{ $delivery->receipt->address }}</p>
    </div>

    @if($delivery->debt)
        <div class="divider"></div>

        <div class="info">
            <p><strong>Deuda:</strong> ${{ number_format($delivery->debt->amount, 2) }}</p>
            <p><strong>Estado de Deuda:</strong>
                @if($delivery->debt->status === 'pending')
                    Pendiente
                @elseif($delivery->debt->status === 'partial_paid')
                    Parcialmente Pagada
                @else
                    Pagada
                @endif
            </p>
        </div>
    @endif

    <div class="info">
        <p>
            <strong>Nota:</strong>
        </p>
        <p>{!! nl2br(e($delivery->notes)) !!}</p>
    </div>

    <div class="divider"></div>

    <div class="footer">
        <p>Gracias por confiar en Yetiexpress</p>
        <p>{{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>

</html>
