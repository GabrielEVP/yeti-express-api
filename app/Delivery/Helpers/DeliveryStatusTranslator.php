<?php

namespace App\Helpers;

class DeliveryStatusTranslator
{
    /**
     * Translate delivery status from English to Spanish
     *
     * @param string $status Status in English
     * @return string Status in Spanish
     */
    public static function toSpanish($status): string
    {
        return match (strtolower($status)) {
            'pending' => 'Pendiente',
            'in_transit' => 'En Tránsito',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
            'paid' => 'Pagado',
            'unpaid' => 'No Pagado',
            'partially_paid' => 'Parcialmente Pagado',
            'partial_paid' => 'Parcialmente Pagado',
            'collected' => 'Cobrado',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
