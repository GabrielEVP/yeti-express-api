<?php

namespace App\Delivery\Controllers;

use App\Core\Controllers\Controller;
use App\Delivery\DomPDF\DomPDFTDelivery;
use App\Delivery\Services\PDFDeliveryService;
use Illuminate\Http\Response;

class DeliveryReportController extends Controller
{
    protected DomPDFTDelivery $pdfService;
    private PDFDeliveryService $PDFDeliveryService;

    public function __construct(DomPDFTDelivery $pdfService, PDFDeliveryService $PDFDeliveryService)
    {
        $this->pdfService = $pdfService;
        $this->PDFDeliveryService = $PDFDeliveryService;
    }

    public function getTicketReportDelivery(string $id): Response
    {
        $delivery = $this->PDFDeliveryService->getTicketReportDelivery($id);
        $pdf = $this->pdfService->generateDeliveryTicket($delivery);

        return $pdf->stream("delivery-ticket.pdf");
    }
}
