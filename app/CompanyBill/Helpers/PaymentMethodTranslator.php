<?php

namespace App\CompanyBill\Helpers;

use App\CompanyBill\Models\Method;
use App\Debt\Models\Method as DebtMethod;

final class PaymentMethodTranslator
{
    public static function toSpanish(Method|DebtMethod|string $method): string
    {
        // Handle CompanyBill Method enum
        if ($method instanceof Method) {
            return match($method) {
                Method::CASH => 'Efectivo',
                Method::MOBILE_PAYMENT => 'Pago móvil',
                Method::BANK_TRANSFERED => 'Transferencia bancaria',
            };
        }

        // Handle Debt Method enum
        if ($method instanceof DebtMethod) {
            return match($method) {
                DebtMethod::Cash => 'Efectivo',
                DebtMethod::MobilePayment => 'Pago móvil',
                DebtMethod::Transfer => 'Transferencia',
            };
        }

        // If a string is passed, try to convert it to an enum first
        try {
            // Try CompanyBill Method enum first
            return self::toSpanish(Method::from($method));
        } catch (\ValueError $e) {
            try {
                // Then try Debt Method enum
                return self::toSpanish(DebtMethod::from($method));
            } catch (\ValueError $e) {
                // If both conversions fail, return the original string
                return $method;
            }
        }
    }
}
