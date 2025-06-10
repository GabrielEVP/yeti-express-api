<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PDFService
{
    public function generateDeliveryTicket($delivery)
    {
        $pdf = PDF::loadView('pdfs.delivery-ticket', [
            'delivery' => $delivery
        ]);

        // Set paper size to 80mm thermal printer width (approximately 226.77 points)
        $pdf->setPaper([0, 0, 226.77, 1000], 'portrait');

        return $pdf;
    }

    public function generateClientDebtReport($client)
    {
        $pdf = PDF::loadView('pdfs.client-debt-report', [
            'client' => $client
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }

    public function generateCourierDeliveriesReport($courier)
    {
        $pdf = PDF::loadView('pdfs.courier-deliveries-report', [
            'courier' => $courier
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }
}