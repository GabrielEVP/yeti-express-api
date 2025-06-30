<?php

namespace App\Delivery\Repositories;

use App\Delivery\DTO\ReportPDFDeliveryDTO;

interface IPDFDeliveryRepository
{
    public function getTicketReportDelivery(string $id): ReportPDFDeliveryDTO;
}
