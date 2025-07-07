<?php

namespace App\Debt\DTO;

use App\Core\DTO\FilterRequestPaginatedDTO;

final class FilterRequestDeliveriesWithDebtDTO extends FilterRequestPaginatedDTO
{
    public ?string $client_id;
    public ?string $status;

    public function __construct(
        string  $search = '',
        string  $sortBy = 'number',
        string  $sortDirection = 'asc',
        ?string $client_id = null,
        ?string $status = null,
        int     $page = 1,
        int     $perPage = 15
    )
    {
        parent::__construct($search, $sortBy, $sortDirection, $page, $perPage);
        $this->client_id = $client_id;
        $this->status = $status;
    }
}
