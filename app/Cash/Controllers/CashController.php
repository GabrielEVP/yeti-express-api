<?php

namespace App\Cash\Controllers;

use App\Cash\DTO\FilterPeriodRequestDTO;
use App\Cash\Services\CashService;
use App\Core\Controllers\Controller;
use App\Shared\Services\AuthHelper;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashController extends Controller
{
    public function __construct(private readonly CashService $cashService)
    {
        Carbon::setLocale('es');
        setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'es');
    }

    public function getDashboardStats(Request $request): JsonResponse
    {
        $filterDTO = FilterPeriodRequestDTO::fromRequest($request);
        $userId = AuthHelper::getUserId();

        $stats = $this->cashService->getDashboardStats($userId, $filterDTO->period, $filterDTO->date);

        return response()->json($stats->toArray());
    }

    public function getCashRegisterReport(Request $request)
    {
        $filterDTO = FilterPeriodRequestDTO::fromRequest($request);
        $reportData = $this->cashService->getCashRegisterReportData($filterDTO->period, $filterDTO->date);
        $pdf = app(\App\Cash\Services\PDFService::class)->generateCashRegisterReport($reportData->toArray());

        return $pdf->download("caja.pdf");
    }
}
