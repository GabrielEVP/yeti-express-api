<?php

namespace App\Courier\DomPDF;

use App\Courier\DTO\ReportPDFAllCourierDTO;
use App\Courier\DTO\ReportPDFCourierDTO;
use Barryvdh\DomPDF\Facade\Pdf;

class DomPDFCourier
{
    public function generateAllCouriersDeliveriesReport(ReportPDFAllCourierDTO $dto): \Barryvdh\DomPDF\PDF
    {
        return PDF::loadView('courier::all-couriers-deliveries-report', [
            'couriers' => $dto->couriers,
            'startDate' => $dto->startDate,
            'endDate' => $dto->endDate,
        ])
            ->setPaper('a4', 'portrait');
    }

    public function generateCourierDeliveriesReport(ReportPDFCourierDTO $dto): \Barryvdh\DomPDF\PDF
    {
        return PDF::loadView('courier::courier-deliveries-report', [
            'courier' => $dto,
            'deliveries' => collect($dto->deliveries),
            'startDate' => $dto->startDate,
            'endDate' => $dto->endDate
        ])
            ->setPaper('a4', 'portrait');
    }
}
