<?php
<?php

namespace App\Debt\DTO;

use JsonSerializable;

class FullPaymentRequestDTO implements JsonSerializable
{
    public function __construct(
        public string $debt_id,
        public string $method
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'debt_id' => $this->debt_id,
            'method' => $this->method
        ];
    }
}
namespace App\Debt\DTO;

use JsonSerializable;

class FullPaymentRequestDTO implements JsonSerializable
{
    public function __construct(
        public int $debt_id,
        public string $method
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            debt_id: $data['debt_id'],
            method: $data['method']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'debt_id' => $this->debt_id,
            'method' => $this->method
        ];
    }
}
