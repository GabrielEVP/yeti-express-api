<?php

namespace App\Delivery\Controllers;

use App\Delivery\Models\Delivery;
use App\Http\Controllers\Controller;
use App\Services\PDFService;
use Illuminate\Support\Facades\Auth;

class DeliveryReportController extends Controller
{
    protected PDFService $pdfService;

    public function __construct(PDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function deliveryTicket(Delivery $delivery): \Illuminate\Http\Response
    {
        $this->authorizeOwner($delivery);

        $delivery->load([
            'service',
            'client',
            'courier',
            'receipt'
        ]);

        $pdf = $this->pdfService->generateDeliveryTicket($delivery);

        return $pdf->stream("delivery-ticket-{$delivery->number}.pdf");
    }

    private function authorizeOwner($model): void
    {
        abort_if(!Auth::user(), 403, 'No tienes permiso para acceder a este recurso.');
    }
}
