<?php

namespace App\Delivery\DTO;

final class FormRequestDeliveryDTO implements \JsonSerializable
{
    public function __construct(
        public string  $payment_type,
        public ?string $notes,
        public int     $service_id,
        public int     $courier_id,
        public int     $client_id,
        public string  $pickup_address,
        public string  $receipt_full_name,
        public string  $receipt_phone,
        public string  $receipt_address,
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            payment_type: $data['payment_type'] ?? '',
            notes: $data['notes'] ?? null,
            service_id: (int)($data['service_id'] ?? 0),
            courier_id: (int)($data['courier_id'] ?? 0),
            client_id: (int)($data['client_id'] ?? 0),
            pickup_address: $data['pickup_address'] ?? '',
            receipt_full_name: $data['receipt']['full_name'] ?? '',
            receipt_phone: $data['receipt']['phone'] ?? '',
            receipt_address: $data['receipt']['address'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'payment_type' => $this->payment_type,
            'notes' => $this->notes,
            'service_id' => $this->service_id,
            'courier_id' => $this->courier_id,
            'client_id' => $this->client_id,
            'pickup_address' => $this->pickup_address,
            'receipt' => [
                'full_name' => $this->receipt_full_name,
                'phone' => $this->receipt_phone,
                'address' => $this->receipt_address,
            ],
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
