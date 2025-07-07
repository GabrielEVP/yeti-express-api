<?php

namespace App\Delivery\DTO;

use JsonSerializable;

class FormRequestStatusDeliveryDTO implements JsonSerializable
{
    public function __construct(
        public string $status
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
