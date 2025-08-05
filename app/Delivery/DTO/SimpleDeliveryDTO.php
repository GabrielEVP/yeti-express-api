<?php

namespace App\Delivery\DTO;

use JsonSerializable;

final class SimpleDeliveryDTO implements JsonSerializable
{
    public int $id;
    public string $number;
    public string $date;
    public string $status;
    public float $amount;

    public string $client_name;
    public string $client_name_source;
    public string $service_name;
    public string $courier_full_name;

    public function __construct(array $data)
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->number = $data['number'] ?? '';
        $this->date = $data['date']->toDateString();
        $this->status = $data['status'] ?? '';
        $this->amount = isset($data['amount']) ? (float) $data['amount'] : 0.0;

        $this->client_name = $data['client_name'] ?? '';
        $this->client_name_source = $data['client_name_source'] ?? 'none';

        $this->service_name = $data['service_name'] ?? '';
        $this->courier_full_name = $data['courier_full_name'] ?? '';
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'date' => $this->date,
            'status' => $this->status,
            'amount' => $this->amount,
            'client_name' => $this->client_name,
            'client_name_source' => $this->client_name_source,
            'service_name' => $this->service_name,
            'courier_full_name' => $this->courier_full_name,
        ];
    }
}
