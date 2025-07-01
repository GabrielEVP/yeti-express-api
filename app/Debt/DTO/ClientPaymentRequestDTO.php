<?php

namespace App\Debt\DTO;

use JsonSerializable;

class ClientPaymentRequestDTO implements JsonSerializable
{
    public function __construct(
        public int $clientId,
        public string $method,
        public ?float $amount = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            clientId: $data['clientId'],
            method: $data['method'],
            amount: isset($data['amount']) ? (float) $data['amount'] : null
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'clientId' => $this->clientId,
            'method' => $this->method,
            'amount' => $this->amount
        ];
    }
}
