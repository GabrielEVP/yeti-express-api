<?php

namespace App\Delivery\DTO;

class FilterRequestDeliveryDTO
{
    public string $search;
    public string $sortBy;
    public string $sortDirection;
    public ?string $status;
    public ?string $service_id;
    public ?string $payment_status;
    public ?string $start_date;
    public ?string $end_date;
    public int $page;
    public int $perPage;

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
        $this->search = $search;
        $this->sortBy = $sortBy;
        $this->sortDirection = strtolower($sortDirection);
        $this->status = $status;
        $this->service_id = $service_id;
        $this->payment_status = $payment_status;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->page = $page > 0 ? $page : 1;
        $this->perPage = $perPage > 0 ? $perPage : 15;
    }
}

