<?php

namespace App\Delivery\Helpers;

use App\Delivery\Models\PaymentType;

class PaymentTypeTranslator
{
    public static function toSpanish(PaymentType $paymentType): string
    {
        return match ($paymentType) {
            PaymentType::FULL => 'Pago Completo',
            PaymentType::PARTIAL => 'Pago Parcial',
        };
    }
}
