<?php

namespace App\Cash\Controllers;

use App\Cash\DTO\FilterDateRangeRequestDTO;
use App\Cash\Services\CashReportService;
use App\Core\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CashReportController extends Controller
{
    public function __construct(private readonly CashReportService $cashReportService)
    {
        Carbon::setLocale('es');
        setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'es');
    }

    public function cashRegisterReport(Request $request): Response
    {
        $filterDTO = FilterDateRangeRequestDTO::fromRequest($request);
        $pdf = $this->cashReportService->generateCashRegisterReport($filterDTO->startDate, $filterDTO->endDate);
        
        return $pdf->stream("reporte-caja-detallado.pdf");
    }

    public function simplifiedCashRegisterReport(Request $request): Response
    {
        $filterDTO = FilterDateRangeRequestDTO::fromRequest($request);
        $pdf = $this->cashReportService->generateSimplifiedCashRegisterReport($filterDTO->startDate, $filterDTO->endDate);

        return $pdf->stream("reporte-cajas-simplificado.pdf");
    }
}
