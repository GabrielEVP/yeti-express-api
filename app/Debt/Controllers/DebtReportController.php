<?php

namespace App\Debt\Controllers;

use App\Client\Models\Client;
use App\Core\Controllers\Controller;
use App\Debt\DomPDF\DomPDFDebt;
use App\Debt\Requests\DebtReportRequest;
use App\Debt\Services\PDFDebtService;
use Illuminate\Support\Facades\Auth;

class DebtReportController extends Controller
{
    protected DomPDFDebt $pdfService;
    protected PDFDebtService $pdfDebtService;

    public function __construct(DomPDFDebt $pdfService, PDFDebtService $pdfDebtService)
    {
        $this->pdfService = $pdfService;
        $this->pdfDebtService = $pdfDebtService;
    }

    public function unpaidDebtsReport(): \Illuminate\Http\Response
    {
        $this->authorizeOwner(new Client());

        $clientsDTO = $this->pdfDebtService->getUnpaidClientsWithDebts();

        $pdf = $this->pdfService->generateUnpaidDebtsReport($clientsDTO);
        return $pdf->stream("unpaid-debts-report.pdf");
    }

    public function clientDebtReport(Client $client, DebtReportRequest $request): \Illuminate\Http\Response
    {
        $this->authorizeOwner($client);

        $dateRangeDTO = $request->toDTO();

        $clientDTO = $this->pdfDebtService->getClientDebtsWithFilters($client, $dateRangeDTO);

        $pdf = $this->pdfService->generateClientDebtReport(
            $clientDTO,
            $dateRangeDTO->startDate,
            $dateRangeDTO->endDate
        );

        return $pdf->stream("client-debt-report-{$client->id}.pdf");
    }

    public function allClientsDebtReport(DebtReportRequest $request): \Illuminate\Http\Response
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

    private function authorizeOwner($model): void
    {
        abort_if(!Auth::user(), 403, 'No tienes permiso para acceder a este recurso.');
    }
}
