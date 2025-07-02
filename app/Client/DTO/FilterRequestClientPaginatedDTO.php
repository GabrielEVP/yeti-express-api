<?php

namespace App\Client\DTO;

use App\Core\DTO\FilterRequestPaginatedDTO;

final class FilterRequestClientPaginatedDTO extends FilterRequestPaginatedDTO
{
    public ?string $type;
    public ?bool $allowCredit;
    public array $select;

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
        parent::__construct($search, $sortBy, $sortDirection, $page, $perPage);
        $this->type = $type;
        $this->allowCredit = $allowCredit;
        $this->select = $select;
    }
}

