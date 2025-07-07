<?php

namespace App\Debt\DTO;

use JsonSerializable;

class ClientDebtStatsDTO implements JsonSerializable
{
    public function __construct(
        public int $total_deliveries_with_debt,
        public float $total_pending
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'total_deliveries_with_debt' => $this->total_deliveries_with_debt,
            'total_pending' => $this->total_pending
        ];
    }
}
