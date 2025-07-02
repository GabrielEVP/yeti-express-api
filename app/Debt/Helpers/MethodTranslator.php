<?php

namespace App\Debt\Helpers;

use App\Debt\Models\Method;

class MethodTranslator
{
    public static function toSpanish(Method $method): string
    {
        return match($method) {
            Method::Cash => 'Efectivo',
            Method::MobilePayment => 'Pago Móvil',
            Method::Transfer => 'Transferencia',
        };
    }
}
