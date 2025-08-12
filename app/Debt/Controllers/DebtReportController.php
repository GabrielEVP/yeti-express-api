<?php

namespace App\Debt\Controllers;

use App\Client\Models\Client;
use App\Core\Controllers\Controller;
use App\Debt\DomPDF\DomPDFDebt;
use App\Debt\Services\PDFDebtService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DebtReportController extends Controller
{
    protected DomPDFDebt $pdfService;
    protected PDFDebtService $pdfDebtService;

    public function __construct(DomPDFDebt $pdfService, PDFDebtService $pdfDebtService)
    {
        $this->pdfService = $pdfService;
        $this->pdfDebtService = $pdfDebtService;
    }

    public function getUnPaidDebtsReport(): Response
    {
        $clients = $this->pdfDebtService->getUnpaidClientsWithDebts();

        $pdf = $this->pdfService->generateUnpaidDebtsReport($clients);

        return $pdf->stream("unpaid-debts-report.pdf");
    }

    public function getClientDebtReport(string $id, Request $request): Response
    {
        // Support both snake_case (preferred) and camelCase query params
        $dateRange = [
            'startDate' => $request->input('start_date', $request->input('startDate')),
            'endDate' => $request->input('end_date', $request->input('endDate')),
        ];

        $client = Client::findOrFail($id);
        $client = $this->pdfDebtService->getClientDebtsWithFilters($client, $dateRange);

        $pdf = $this->pdfService->generateClientDebtReport(
            $client,
            $dateRange['startDate'],
            $dateRange['endDate']
        );


        return $pdf->stream("client-debt-report.pdf");
    }

    public function getAllClientsDebtReport(Request $request): Response
    {
        // Support both snake_case (preferred) and camelCase query params
        $dateRange = [
            'startDate' => $request->input('start_date', $request->input('startDate')),
            'endDate' => $request->input('end_date', $request->input('endDate')),
        ];

        $clients = $this->pdfDebtService->getAllClientsDebtsWithFilters($dateRange);

        $pdf = $this->pdfService->generateAllClientsDebtReport(
            $clients,
            $dateRange['startDate'],
            $dateRange['endDate']
        );

        return $pdf->stream("all-clients-debt-report.pdf");
    }
}
