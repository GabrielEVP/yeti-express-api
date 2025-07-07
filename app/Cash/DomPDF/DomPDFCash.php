<?php

namespace App\Cash\DomPDF;

use Barryvdh\DomPDF\Facade\Pdf;

class DomPDFCash
{
    public function generateCashRegisterReport(array $reportData): \Barryvdh\DomPDF\PDF
    {
        $pdf = PDF::loadView('cash::cash-register-report', $reportData);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function generateSimplifiedCashRegisterReport(array $reportData): \Barryvdh\DomPDF\PDF
    {
        if (!empty($reportData['all_deliveries'])) {
            usort($reportData['all_deliveries'], function ($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
        }

        $pdf = PDF::loadView('cash::simplified-cash-register-report', $reportData);
        $pdf->setPaper('a4', 'portrait');
        return $pdf;
    }
}
