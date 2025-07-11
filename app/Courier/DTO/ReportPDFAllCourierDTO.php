<?php

namespace App\Courier\DTO;

final class ReportPDFAllCourierDTO
{
    public array $couriers;
    public ?string $startDate;
    public ?string $endDate;

    public function __construct(array $couriers, ?string $startDate = null, ?string $endDate = null)
    {
        $this->couriers = array_map(function ($courier) {
            return [
                'id' => (string)($courier['id'] ?? ''),
                'full_name' => $courier['full_name'] ?? '',
                'phone' => $courier['phone'] ?? '',
                'deliveries' => array_map(function ($delivery) {
                    return [
                        'number' => $delivery['number'],
                        'date' => $delivery['date'],
                        'status' => is_object($delivery['status']) ? $delivery['status']->value : $delivery['status'],
                        'client_name' => $delivery['client_name'] ?? '-',
                        'is_anonymous_client' => $delivery['is_anonymous_client'] ?? false,
                        'amount' => (float)$delivery['amount'],
                        'cancellation_notes' => $delivery['cancellation_notes'] ?? null,
                    ];
                }, $courier['deliveries'] ?? []),
            ];
        }, $couriers);

        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
}
