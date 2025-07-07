<?php

namespace App\Delivery\Helpers;

use App\Delivery\Models\PaymentStatus;
use App\Delivery\Models\Status;

class PaymentStatusTranslator
{
    public static function toSpanish(PaymentStatus $paymentStatus): string
    {
        return match ($paymentStatus) {
            PaymentStatus::PENDING => 'Pendiente',
            PaymentStatus::PARTIAL_PAID => 'Parcialmente Pagado',
            PaymentStatus::PAID => 'Pagado',
        };
    }
}
