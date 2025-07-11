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
        public ?string $anonymous_client_legal_name = null,
        public ?string $anonymous_client_type = null,
        public ?string $anonymous_client_registration_number = null,
        public ?string $anonymous_client_phone = null,
        public ?string $anonymous_client_address = null
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
            anonymous_client_legal_name: $data['anonymous_client']['legal_name'] ?? null,
            anonymous_client_type: $data['anonymous_client']['type'] ?? null,
            anonymous_client_registration_number: $data['anonymous_client']['registration_number'] ?? null,
            anonymous_client_phone: $data['anonymous_client']['phone'] ?? null,
            anonymous_client_address: $data['anonymous_client']['address'] ?? null
        );
    }

    public function toArray(): array
    {
        $data = [
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

        if (empty($this->client_id) && $this->anonymous_client_legal_name) {
            $data['anonymous_client'] = [
                'legal_name' => $this->anonymous_client_legal_name,
                'type' => $this->anonymous_client_type,
                'registration_number' => $this->anonymous_client_registration_number,
                'phone' => $this->anonymous_client_phone,
            ];
        }

        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
