<?php

namespace App\Client\DTO;

final class FilterRequestClientDTO
{
    public string $search;
    public string $sortBy;
    public string $sortDirection;
    public ?string $type;
    public ?bool $allowCredit;
    public array $select;
    public int $page;
    public int $perPage;

    public function __construct(
        string  $search = '',
        string  $sortBy = 'legal_name',
        string  $sortDirection = 'asc',
        ?string $type = null,
        ?bool   $allowCredit = null,
        array   $select = [],
        int     $page = 1,
        int     $perPage = 15
    )
    {
        $this->search = $search;
        $this->sortBy = $sortBy;
        $this->sortDirection = strtolower($sortDirection);
        $this->type = $type;
        $this->allowCredit = $allowCredit;
        $this->select = $select;
        $this->page = $page > 0 ? $page : 1;
        $this->perPage = $perPage > 0 ? $perPage : 15;
    }
}

