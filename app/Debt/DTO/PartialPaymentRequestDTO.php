<?php

namespace App\Debt\DTO;

use JsonSerializable;

class PartialPaymentRequestDTO implements JsonSerializable
{
    public function __construct(
        public int    $debt_id,
        public float  $amount,
        public string $method
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            debt_id: $data['debt_id'],
            amount: (float)$data['amount'],
            method: $data['method']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'debt_id' => $this->debt_id,
            'amount' => $this->amount,
            'method' => $this->method
        ];
    }
}

