<?php

namespace App\Delivery\DTO;

use Illuminate\Support\Collection;

class FilterDeliveryPaginatedDTO
{
    public array $items;
    public int $currentPage;
    public int $perPage;
    public int $total;

    public function __construct(Collection $items, int $currentPage, int $perPage, int $total)
    {
        $this->items = $items->toArray();
        $this->currentPage = $currentPage;
        $this->perPage = $perPage;
        $this->total = $total;
    }
}
