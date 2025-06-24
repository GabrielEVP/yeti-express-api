<?php

namespace App\CompanyBill\Helpers;

use App\CompanyBill\Models\Method;

final class PaymentMethodTranslator
{
    private const TRANSLATIONS = [
        'cash' => 'Efectivo',
        'mobile_payment' => 'Pago móvil',
        'bank_transfered' => 'Transferencia bancaria',
    ];

    public static function toSpanish(Method|string $method): string
    {
        $key = $method instanceof Method ? $method->value : $method;

        return self::TRANSLATIONS[$key] ?? $key;
    }
}
