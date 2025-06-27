<?php

namespace App\Delivery\Services;

use App\Delivery\Models\Delivery;
use App\Delivery\Models\DeliveryEvent;

class DeliveryEventService
{

    public static function log(string $event, Delivery $delivery, string $section = null, string $referenceTable = null, int $referenceId = null): ?DeliveryEvent
    {
        if (!$delivery->id) {
            return null;
        }

        return DeliveryEvent::create([
            'delivery_id' => $delivery->id,
            'event' => $event,
            'section' => $section ?? 'deliveries',
            'reference_table' => $referenceTable ?? 'deliveries',
            'reference_id' => $referenceId ?? $delivery->id,
        ]);
    }
}
