<?php
namespace App\Http\Services;

use App\Models\DeliveryEvent;
use App\Models\Delivery;

class DeliveryEventService
{
    /**
     * Registra un evento relacionado con un delivery
     *
     * @param string $event Tipo de evento
     * @param Delivery $delivery Instancia del delivery
     * @param string|null $section Sección relacionada
     * @param string|null $referenceTable Tabla de referencia
     * @param int|null $referenceId ID de referencia
     * @return DeliveryEvent|null
     */
    public static function log(string $event, Delivery $delivery, string $section = null, string $referenceTable = null, int $referenceId = null): ?DeliveryEvent
    {
        if (!$delivery || !$delivery->id) {
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
