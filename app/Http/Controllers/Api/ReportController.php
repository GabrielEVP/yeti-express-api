<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Client;
use App\Models\Courier;
use App\Services\PDFService;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    protected $pdfService;

    public function __construct(PDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function deliveryTicket(Delivery $delivery)
    {
        $this->authorizeOwner($delivery);

        $delivery->load([
            'service',
            'client',
            'clientAddress',
            'courier',
            'receipt'
        ]);

        $pdf = $this->pdfService->generateDeliveryTicket($delivery);

        return $pdf->stream("delivery-ticket-{$delivery->number}.pdf");
    }

    public function clientDebtReport(Client $client)
    {
        $this->authorizeOwner($client);

        $client->load([
            'debts',
            'debts.payments',
            'debts.delivery',
            'debts.delivery.service'
        ]);

        $pdf = $this->pdfService->generateClientDebtReport($client);

        return $pdf->stream("client-debt-report-{$client->id}.pdf");
    }

    public function courierDeliveriesReport(Courier $courier)
    {
        $this->authorizeOwner($courier);

        $courier->load([
            'deliveries',
            'deliveries.service',
            'deliveries.client',
            'deliveries.receipt'
        ]);

        $pdf = $this->pdfService->generateCourierDeliveriesReport($courier);

        return $pdf->stream("courier-deliveries-report-{$courier->id}.pdf");
    }

    private function authorizeOwner($model)
    {
        abort_if($model->user_id !== auth()->id(), 403, 'No tienes permiso para acceder a este recurso.');
    }
}