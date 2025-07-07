<?php

namespace App\Debt\Controllers;

use App\Client\Models\Client;
use App\Core\Controllers\Controller;
use App\Debt\DomPDF\DomPDFDebt;
use App\Debt\Services\PDFDebtService;
use Illuminate\Http\Request;

class DebtReportController extends Controller
{
    protected DomPDFDebt $pdfService;
    protected PDFDebtService $pdfDebtService;

    public function __construct(DomPDFDebt $pdfService, PDFDebtService $pdfDebtService)
    {
        $this->pdfService = $pdfService;
        $this->pdfDebtService = $pdfDebtService;
    }

    public function getUnPaidDebtsReport(): \Illuminate\Http\Response
    {
        $clientsDTO = $this->pdfDebtService->getUnpaidClientsWithDebts();

        $pdf = $this->pdfService->generateUnpaidDebtsReport($clientsDTO);
        return $pdf->stream("unpaid-debts-report.pdf");
    }

    public function getClientDebtReport(Client $client, Request $request): \Illuminate\Http\Response
    {
        $request->input('startDate');
        $request->input('endDate');

        $clientDTO = $this->pdfDebtService->getClientDebtsWithFilters($client, $dateRangeDTO);

        $pdf = $this->pdfService->generateClientDebtReport(
            $clientDTO,
            $dateRangeDTO->startDate,
            $dateRangeDTO->endDate
        );

        return $pdf->stream("client-debt-report-{$client->id}.pdf");
    }

    public function getAllClientsDebtReport(Request $request): \Illuminate\Http\Response
    {
        $dateRangeDTO = $request->toDTO();

        $clientsDTO = $this->pdfDebtService->getAllClientsDebtsWithFilters($dateRangeDTO);

        $pdf = $this->pdfService->generateAllClientsDebtReport(
            $clientsDTO,
            $dateRangeDTO->startDate,
            $dateRangeDTO->endDate
        );

        return $pdf->stream("all-clients-debt-report.pdf");
    }


}
