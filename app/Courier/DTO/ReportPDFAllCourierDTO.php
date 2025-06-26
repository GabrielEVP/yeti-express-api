<?php

namespace App\Courier\DTO;

use Illuminate\Support\Collection;

final class ReportPDFAllCourierDTO
{
    public Collection $couriers;
    public string $startDate;
    public string $endDate;

    public function __construct(Collection $couriers, string $startDate, string $endDate)
    {
        $this->couriers = $couriers;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
}
