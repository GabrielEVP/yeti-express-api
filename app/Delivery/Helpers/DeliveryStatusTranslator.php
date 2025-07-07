<?php

namespace App\Delivery\Helpers;

use App\Delivery\Models\Status;
use App\Delivery\Models\PaymentStatus;

class DeliveryStatusTranslator
{
    public static function toSpanish(Status $status): string
    {
        return match ($status) {
            Status::PENDING => 'Pendiente',
            Status::IN_TRANSIT => 'En Tránsito',
            Status::DELIVERED => 'Entregado',
            Status::CANCELLED => 'Cancelado',
        };
    }
}
