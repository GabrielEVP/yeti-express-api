<?php

namespace App\Client\DTO;

use Illuminate\Support\Collection;

final class FilterClientPaginatedDTO
{
    /** @var FilterClientDTO[] */
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
