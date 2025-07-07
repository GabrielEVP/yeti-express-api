<?php

namespace App\Delivery\DomPDF;

use Barryvdh\DomPDF\Facade\Pdf;

class DomPDFTDelivery
{
    public function generateDeliveryTicket($delivery): \Barryvdh\DomPDF\PDF
    {
        $pdf = PDF::loadView('Delivery::delivery-ticket', [
            'delivery' => $delivery
        ]);

        $pdf->setPaper([0, 0, 226.77, 1000], 'portrait');

        return $pdf;
    }
}
