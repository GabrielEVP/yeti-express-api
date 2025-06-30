<?php

namespace App\Debt\Controllers;

use App\Client\Models\Client;
use App\Debt\Services\PDFDebtService;
use App\Http\Controllers\Controller;
use App\Services\PDFService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DebtReportController extends Controller
{
    protected PDFService $pdfService;
    protected PDFDebtService $pdfDebtService;

    public function __construct(PDFService $pdfService, PDFDebtService $pdfDebtService)
    {
        $this->pdfService = $pdfService;
        $this->pdfDebtService = $pdfDebtService;
    }

    public function unpaidDebtsReport(): \Illuminate\Http\Response
    {
        $this->authorizeOwner(new Client());

        $clients = $this->pdfDebtService->getUnpaidClientsWithDebts();

        $pdf = $this->pdfService->generateUnpaidDebtsReport($clients);
        return $pdf->stream("unpaid-debts-report.pdf");
    }

    public function clientDebtReport(Client $client, Request $request): \Illuminate\Http\Response
    {
        $startDate = $request->get('start_date') ?: \Carbon\Carbon::today()->format('Y-m-d');
        $endDate = $request->get('end_date') ?: \Carbon\Carbon::today()->format('Y-m-d');

        $this->authorizeOwner($client);

        $client = $this->pdfDebtService->getClientDebtsWithFilters($client, $startDate, $endDate);

        $pdf = $this->pdfService->generateClientDebtReport($client, $startDate, $endDate);
        return $pdf->stream("client-debt-report-{$client->id}.pdf");
    }

    public function allClientsDebtReport(Request $request): \Illuminate\Http\Response
    {
        $startDate = $request->get('start_date') ?: \Carbon\Carbon::today()->format('Y-m-d');
        $endDate = $request->get('end_date') ?: \Carbon\Carbon::today()->format('Y-m-d');

        $clients = $this->pdfDebtService->getAllClientsDebtsWithFilters($startDate, $endDate);

        $pdf = $this->pdfService->generateAllClientsDebtReport($clients, $startDate, $endDate);
        return $pdf->stream("all-clients-debt-report.pdf");
    }

    private function authorizeOwner($model): void
    {
        abort_if(!Auth::user(), 403, 'No tienes permiso para acceder a este recurso.');
    }
}
