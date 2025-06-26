<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PDFService
{
    public function generateDeliveryTicket($delivery): \Barryvdh\DomPDF\PDF
    {
        $pdf = PDF::loadView('pdfs.delivery-ticket', [
            'delivery' => $delivery
        ]);

        $pdf->setPaper([0, 0, 226.77, 1000], 'portrait');

        return $pdf;
    }

    public function generateClientDebtReport($client, $startDate, $endDate): \Barryvdh\DomPDF\PDF
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.client-debt-report', [
            'client' => $client,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function generateAllClientsDebtReport($clients, $startDate, $endDate): \Barryvdh\DomPDF\PDF
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.all-clients-debt-report', [
            'clients' => $clients,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }


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

    public function generatePreviousPaymentsReport(array $reportData): \Barryvdh\DomPDF\PDF
    {
        // Ordenar los pagos por número de entrega
        if (isset($reportData['previousDayPayments']) && !empty($reportData['previousDayPayments'])) {
            usort($reportData['previousDayPayments'], function ($a, $b) {
                return $a['number'] <=> $b['number'];
            });
        }

        $pdf = PDF::loadView('pdfs.previous-payments-report', $reportData);
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
