<?php

namespace App\Core\DTO;

class FilterRequestPaginatedDTO
{
    public string $search;
    public string $sortBy;
    public string $sortDirection;
    public int $page;
    public int $perPage;

    public function __construct(
        string $search = '',
        string $sortBy = 'id',
        string $sortDirection = 'asc',
        int    $page = 1,
        int    $perPage = 15
    )
    {
        $this->search = $search;
        $this->sortBy = $sortBy;
        $this->sortDirection = strtolower($sortDirection);
        $this->page = $page > 0 ? $page : 1;
        $this->perPage = $perPage > 0 ? $perPage : 15;
    }
}
