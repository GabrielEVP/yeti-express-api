<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\PDFService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DebtReportController extends Controller
{
    protected PDFService $pdfService;

    public function __construct(PDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Generate report of all clients with unpaid debts
     * No parameters needed
     */
    public function unpaidDebtsReport(): \Illuminate\Http\Response
    {
        $this->authorizeOwner(new Client());

        // Get clients with pending or partially paid debts
        $clients = Client::whereHas('debts', function ($query) {
            $query->whereIn('status', ['pending', 'partial_paid']);
        })
            ->with([
                'debts' => function ($query) {
                    $query->whereIn('status', ['pending', 'partial_paid'])
                        ->with(['payments', 'delivery.service']);
                }
            ])
            ->get();

        // Generate PDF
        $pdf = $this->pdfService->generateUnpaidDebtsReport($clients);
        return $pdf->stream("unpaid-debts-report.pdf");
    }

    public function clientDebtReport(Client $client, Request $request): \Illuminate\Http\Response
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$startDate || !$endDate) {
            abort(400, 'Se requieren fechas de inicio y fin para generar el reporte.');
        }

        $this->authorizeOwner($client);

        $deliveryQuery = $client->deliveries();
        $deliveryQuery->byPeriod($startDate, $endDate);
        $delivery = $deliveryQuery->with(['debt', 'debt.payments', 'service'])->get();

        $pdf = $this->pdfService->generateClientDebtReport($client, $delivery, $startDate, $endDate);
        return $pdf->stream("client-debt-report-{$client->id}.pdf");
    }

    public function allClientsDebtReport(Request $request): \Illuminate\Http\Response
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$startDate || !$endDate) {
            abort(400, 'Se requieren fechas de inicio y fin para generar el reporte.');
        }

        $clients = Client::where(function ($query) use ($startDate, $endDate) {
            $query->whereHas('debts')
                ->orWhereHas('deliveries', function ($q) use ($startDate, $endDate) {
                    $q->byPeriod($startDate, $endDate)
                        ->whereHas('debt');
                });
        })
            ->with([
                'debts',
                'deliveries' => function ($query) use ($startDate, $endDate) {
                    $query->byPeriod($startDate, $endDate)
                        ->with(['debt', 'debt.payments', 'service']);
                }
            ])
            ->get();

        $pdf = $this->pdfService->generateAllClientsDebtReport($clients, $startDate, $endDate);
        return $pdf->stream("all-clients-debt-report.pdf");
    }

    private function authorizeOwner($model): void
    {
        abort_if(!Auth::user(), 403, 'No tienes permiso para acceder a este recurso.');
    }
}
