<?php

namespace App\Debt\DTO;

use Illuminate\Support\Collection;

class DebtDeliveryPaginatedDTO
{
    public function __construct(
        public Collection $data,
        public int        $currentPage,
        public int        $perPage,
        public int        $total
    )
    {
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'current_page' => $this->currentPage,
            'per_page' => $this->perPage,
            'total' => $this->total

        ];
    }
}
