<?php

namespace App\Debt\DTO;

use JsonSerializable;

class FormRequestPayPartialDTO implements JsonSerializable
{
    public function __construct(
        public int    $client_id,
        public float  $amount,
        public string $method
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            client_id: $data['client_id'],
            amount: (float)$data['amount'],
            method: $data['method']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'client_id' => $this->client_id,
            'amount' => $this->amount,
            'method' => $this->method
        ];
    }
}

