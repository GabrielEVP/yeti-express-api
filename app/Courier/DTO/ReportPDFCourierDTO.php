<?php

namespace App\Courier\DTO;

final class ReportPDFCourierDTO
{
    public string $id;
    public string $first_name;
    public string $last_name;
    public string $phone;
    public ?string $startDate = null;
    public ?string $endDate = null;
    public array $deliveries = [];

    public function __construct(array $data, ?string $startDate = null, ?string $endDate = null)
    {
        $this->id = (string)$data['id'];
        $this->first_name = $data['first_name'];
        $this->last_name = $data['last_name'] ?? '';
        $this->phone = $data['phone'];
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        foreach ($data['deliveries'] ?? [] as $delivery) {
            $this->deliveries[] = [
                'id' => $delivery['id'],
                'number' => $delivery['id'],
                'date' => $delivery['created_at'],
                'client' => $delivery['client'] ?? '-',
                'service' => $delivery['service'] ?? '-',
                'receipt' => $delivery['receipt'] ?? '-',
                'amount' => $delivery['amount'],
                'status' => $delivery['status'] ?? 'pending',
            ];
        }
    }
}
