<?php

namespace App\Debt\DTO;

use JsonSerializable;

class UnpaidDebtsAmountDTO implements JsonSerializable
{
    public function __construct(
        public float $amount
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->amount
        ];
    }
}
