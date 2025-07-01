<?php

namespace App\Debt\DTO;

use JsonSerializable;

class DeliveryWithDebtDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $number,
        public string $date,
        public string $payment_status,
        public int $debt_id,
        public float $debt_amount,
        public float $debt_remaining_amount
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            number: $data['number'] ?? '',
            date: $data['date'] ?? '',
            payment_status: $data['payment_status'] ?? '',
            debt_id: $data['debt_id'] ?? 0,
            debt_amount: $data['amount'] ?? 0,
            debt_remaining_amount: $data['debt_remaining_amount'] ?? 0
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'date' => $this->date,
            'payment_status' => $this->payment_status,
            'debt_id' => $this->debt_id,
            'debt_amount' => $this->debt_amount,
            'debt_remaining_amount' => $this->debt_remaining_amount
        ];
    }
}
