<?php

namespace App\Debt\DTO;

use JsonSerializable;

class ClientWithDebtDTO implements JsonSerializable
{
    public function __construct(
        public int    $id,
        public string $legal_name,
        public string $registration_number,
        public int    $debt_counts,
        public float  $total_pending
    )
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'legal_name' => $this->legal_name,
            'registration_number' => $this->registration_number,
            'debt_counts' => $this->debt_counts,
            'total_pending' => $this->total_pending
        ];
    }
}
