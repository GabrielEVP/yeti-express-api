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

    public function generateClientDebtReport($client, $deliveries, $startDate, $endDate): \Barryvdh\DomPDF\PDF
    {
        $pdf = PDF::loadView('pdfs.client-debt-report', [
            'client' => $client,
            'deliveries' => $deliveries,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function generateAllClientsDebtReport($clients, $startDate, $endDate): \Barryvdh\DomPDF\PDF
    {
        $pdf = PDF::loadView('pdfs.all-clients-debt-report', [
            'clients' => $clients,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function generateCourierDeliveriesReport($courier, $deliveries = null, $startDate = null, $endDate = null): \Barryvdh\DomPDF\PDF
    {
        $pdf = PDF::loadView('pdfs.courier-deliveries-report', [
            'courier' => $courier,
            'deliveries' => $deliveries ?? $courier->deliveries,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function generateAllCouriersDeliveriesReport($couriers, $startDate, $endDate): \Barryvdh\DomPDF\PDF
    {
        $pdf = PDF::loadView('pdfs.all-couriers-deliveries-report', [
            'couriers' => $couriers,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function generateCashRegisterReport(array $reportData): \Barryvdh\DomPDF\PDF
    {
        $pdf = PDF::loadView('pdfs.cash-register-report', $reportData);

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
