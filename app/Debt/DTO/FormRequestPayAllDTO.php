<?php

namespace App\Debt\DTO;

use JsonSerializable;

class FormRequestPayAllDTO implements JsonSerializable
{
    public function __construct(
        public int    $client_id,
        public string $method
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            client_id: $data['client_id'],
            method: $data['method'],
        );
    }

    public function toArray(): array
    {
        return [
            'client_id' => $this->client_id,
            'method' => $this->method,
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'client_id' => $this->client_id,
            'method' => $this->method
        ];
    }
}

