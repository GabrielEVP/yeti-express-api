<?php

namespace App\Employee\DomPDF;

use Barryvdh\DomPDF\Facade\Pdf;

class DomPDFEmployee
{
    public function generateEventReport($event, $startDate, $endDate): \Barryvdh\DomPDF\PDF
    {
        $pdf = Pdf::loadView('employee::event_report', [
            'events' => $event,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }
}

