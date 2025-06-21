<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Services\PDFService;
use Illuminate\Http\Request;
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
