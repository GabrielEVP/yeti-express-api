<?php

namespace App\Courier\DTO;

final class ReportPDFCourierDTO
{
    public string $id;
    public string $full_name;
    public string $phone;
    public ?string $startDate = null;
    public ?string $endDate = null;
    public array $deliveries = [];

    public function __construct(array $data, ?string $startDate = null, ?string $endDate = null)
    {
        $this->id = (string)$data['id'];
        $this->full_name = $data['full_name'];
        $this->phone = $data['phone'];
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        foreach ($data['deliveries'] ?? [] as $delivery) {
            $this->deliveries[] = [
                'number' => $delivery['number'],
                'date' => $delivery['date'],
                'status' => $delivery['status'],
                'client_name' => $delivery['client_name'],
                'is_anonymous_client' => $delivery['is_anonymous_client'] ?? false,
                'amount' => (float)$delivery['amount'],
                'cancellation_notes' => $delivery['cancellation_notes'],
            ];
        }
    }
}
