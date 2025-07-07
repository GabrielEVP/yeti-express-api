<?php

namespace App\Delivery\DTO;

use App\Core\DTO\FilterRequestPaginatedDTO;

class FilterRequestDeliveryPaginatedDTO extends FilterRequestPaginatedDTO
{
    public ?string $status;
    public ?string $service_id;
    public ?string $payment_status;
    public ?string $start_date;
    public ?string $end_date;

    public function __construct(
        string  $search = '',
        string  $sortBy = 'number',
        string  $sortDirection = 'asc',
        ?string $status = null,
        ?string $service_id = null,
        ?string $payment_status = null,
        ?string $start_date = null,
        ?string $end_date = null,
        int     $page = 1,
        int     $perPage = 15
    )
    {
        parent::__construct($search, $sortBy, $sortDirection, $page, $perPage);
        $this->status = $status;
        $this->service_id = $service_id;
        $this->payment_status = $payment_status;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }
}

