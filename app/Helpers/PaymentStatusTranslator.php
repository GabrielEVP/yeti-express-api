<?php

namespace App\Helpers;

class PaymentStatusTranslator
{
    /**
     * Translate payment statuses from English to Spanish
     *
     * @param string $status Payment status in English
     * @return string Payment status in Spanish
     */
    public static function toSpanish($status): string
    {
        return match (strtolower($status)) {
            'pending' => 'Pendiente',
            'partial_paid' => 'Parcialmente Pagado',
            'paid' => 'Pagado',
            'cancelled' => 'Cancelado',
            'refunded' => 'Reembolsado',
            'delivered' => 'Entregado',
            'in_transit' => 'En Tránsito',
            'received' => 'Recibido',
            'completed' => 'Completado',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
