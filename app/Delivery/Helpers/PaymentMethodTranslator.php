<?php

namespace App\Helpers;

class PaymentMethodTranslator
{
    /**
     * Translate payment methods from English to Spanish
     *
     * @param string $method Payment method in English
     * @return string Payment method in Spanish
     */
    public static function toSpanish($method): string
    {
        return match (strtolower($method)) {
            'cash' => 'Efectivo',
            'credit_card' => 'Tarjeta de Crédito',
            'debit_card' => 'Tarjeta de Débito',
            'bank_transfer' => 'Transferencia Bancaria',
            'check' => 'Cheque',
            'cheque' => 'Cheque',
            'deposit' => 'Depósito',
            'online_payment' => 'Pago en Línea',
            'mobile_payment' => 'Pago Móvil',
            'credit' => 'Crédito',
            'refund' => 'Reembolso',
            'transfer' => 'Transferencia',
            'paypal' => 'PayPal',
            'cash_on_delivery' => 'Contra Entrega',
            'wallet' => 'Billetera Digital',
            'venmo' => 'Venmo',
            'zelle' => 'Zelle',
            'bizum' => 'Bizum',
            'full' => 'Pago Completo',
            'partial' => 'Pago Parcial',
            default => ucfirst(str_replace('_', ' ', $method)),
        };
    }
}
