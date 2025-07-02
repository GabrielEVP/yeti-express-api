<?php

namespace App\Cash\Services;

use App\Cash\DomPDF\DomPDFCash;

class PDFService
{
    public function __construct(private readonly DomPDFCash $pdfGenerator)
    {
    }

    public function generateCashRegisterReport(array $reportData): \Barryvdh\DomPDF\PDF
    {
        return $this->pdfGenerator->generateCashRegisterReport($reportData);
    }

    public function generateSimplifiedCashRegisterReport(array $reportData): \Barryvdh\DomPDF\PDF
    {
        return $this->pdfGenerator->generateSimplifiedCashRegisterReport($reportData);
    }
}
