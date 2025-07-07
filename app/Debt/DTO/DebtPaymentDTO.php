<?php

namespace App\Debt\DTO;

use App\Debt\Models\DebtPayment;
use JsonSerializable;

class DebtPaymentDTO implements JsonSerializable
{
    public function __construct(
        public int    $id,
        public string $date,
        public float  $amount,
        public string $method,
        public int    $debt_id,
    )
    {
    }

    public static function fromModel(DebtPayment $payment): self
    {
        return new self(
            id: $payment->id,
            date: $payment->date->format('Y-m-d H:i:s'),
            amount: (float)$payment->amount,
            method: $payment->method->value,
            debt_id: $payment->debt_id,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'amount' => $this->amount,
            'method' => $this->method,
            'debt_id' => $this->debt_id,
        ];
    }
}
