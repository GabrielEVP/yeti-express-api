<?php

namespace App\Cash\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PDFService
{


    public function generateCashRegisterReport(array $reportData): \Barryvdh\DomPDF\PDF
    {
        // Establecer la configuración para permitir más memoria y tiempo de procesamiento para reportes grandes
        ini_set('memory_limit', '256M');
        set_time_limit(120);

        $pdf = PDF::loadView('pdfs.cash-register-report', $reportData);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function generateSimplifiedCashRegisterReport(array $reportData): \Barryvdh\DomPDF\PDF
    {
        // Ordenar las entregas por fecha más reciente primero
        if (isset($reportData['all_deliveries']) && !empty($reportData['all_deliveries'])) {
            usort($reportData['all_deliveries'], function ($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
        }

        $pdf = PDF::loadView('pdfs.simplified-cash-register-report', $reportData);
        $pdf->setPaper('a4', 'portrait');
        return $pdf;
    }


}
