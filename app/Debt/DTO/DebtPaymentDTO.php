<?php

namespace App\Debt\DTO;

use App\Debt\Models\DebtPayment;
use JsonSerializable;

class DebtPaymentDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $date,
        public float $amount,
        public string $method,
        public ?string $notes,
        public int $debt_id,
        public int $user_id,
        public ?DebtDTO $debt = null
    ) {}

    public static function fromModel(DebtPayment $payment): self
    {
        return new self(
            id: $payment->id,
            date: $payment->date->format('Y-m-d H:i:s'),
            amount: (float) $payment->amount,
            method: $payment->method,
            notes: $payment->notes,
            debt_id: $payment->debt_id,
            user_id: $payment->user_id,
            debt: $payment->relationLoaded('debt') ? DebtDTO::fromModel($payment->debt) : null
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'amount' => $this->amount,
            'method' => $this->method,
            'notes' => $this->notes,
            'debt_id' => $this->debt_id,
            'user_id' => $this->user_id,
            'debt' => $this->debt
        ];
    }
}
