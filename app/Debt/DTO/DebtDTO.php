<?php

namespace App\Debt\DTO;

use App\Debt\Models\Debt;
use JsonSerializable;

class DebtDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public float $amount,
        public string $status,
        public int $client_id,
        public ?int $delivery_id,
        public int $user_id
    ) {}

    public static function fromModel(Debt $debt): self
    {
        return new self(
            id: $debt->id,
            amount: (float) $debt->amount,
            status: $debt->status,
            client_id: $debt->client_id,
            delivery_id: $debt->delivery_id,
            user_id: $debt->user_id
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'status' => $this->status,
            'client_id' => $this->client_id,
            'delivery_id' => $this->delivery_id,
            'user_id' => $this->user_id
        ];
    }
}
