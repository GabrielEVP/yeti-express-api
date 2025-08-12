<?php

namespace App\Debt\Controllers;

use App\Client\Models\Client;
use App\Core\Controllers\Controller;
use App\Debt\DomPDF\DomPDFDebt;
use App\Debt\Services\PDFDebtService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClientDebtReportController extends Controller
{
    protected DomPDFDebt $pdfService;
    protected PDFDebtService $pdfDebtService;

    public function __construct(DomPDFDebt $pdfService, PDFDebtService $pdfDebtService)
    {
        $this->pdfService = $pdfService;
        $this->pdfDebtService = $pdfDebtService;
    }

    /**
     * Generate a PDF report for a specific client's unpaid debts
     * 
     * @param string $id Client ID
     * @param Request $request HTTP request
     * @return Response PDF download response
     */
    public function getClientUnpaidDebtsReport(string $id, Request $request): Response
    {
        $client = Client::findOrFail($id);
        $clientWithDebts = $this->pdfDebtService->getSpecificClientWithUnpaidDebts($client);

        $pdf = $this->pdfService->generateClientUnpaidDebtsReport($clientWithDebts);

        return $pdf->stream("client-unpaid-debts-report-{$client->id}.pdf");
    }
}
