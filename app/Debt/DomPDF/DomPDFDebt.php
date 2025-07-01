<?php

namespace App\Debt\DomPDF;

use Barryvdh\DomPDF\Facade\Pdf;

class DomPDFDebt
{
    public function generateClientDebtReport($client, $startDate, $endDate): \Barryvdh\DomPDF\PDF
    {
        $pdf = Pdf::loadView('pdfs.client-debt-report', [
            'client' => $client,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function generateAllClientsDebtReport($clients, $startDate, $endDate): \Barryvdh\DomPDF\PDF
    {
        $pdf = Pdf::loadView('pdfs.all-clients-debt-report', [
            'clients' => $clients,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function generateUnpaidDebtsReport($clients): \Barryvdh\DomPDF\PDF
    {
        $pdf = PDF::loadView('pdfs.unpaid-debts-report', [
            'clients' => $clients,
            'generatedAt' => now()
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }
}
