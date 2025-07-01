<?php

namespace App\Debt\DTO;

use JsonSerializable;

class FullPaymentRequestDTO implements JsonSerializable
{
    public function __construct(
        public string $debt_id,
        public string $method
    )
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'debt_id' => $this->debt_id,
            'method' => $this->method
        ];
    }
}

