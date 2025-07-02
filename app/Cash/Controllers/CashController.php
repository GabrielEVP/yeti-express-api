<?php

namespace App\Cash\Controllers;

use App\Cash\Services\CashService;
use App\Core\Controllers\Controller;
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
        $period = $request->input('period', 'day');
        $date = $request->input('date', now()->toDateString());
        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        $stats = $this->cashService->getDashboardStats($userId, $period, $date);

        return response()->json($stats);
    }

    public function getCashRegisterReport(Request $request)
    {
        $period = $request->input('period', 'day');
        $date = $request->input('date', now()->toDateString());

        $reportData = $this->cashService->getCashRegisterReportData($period, $date);

        $pdf = app(\App\Cash\Services\PDFService::class)->generateCashRegisterReport($reportData);

        $filename = "caja";
        if ($period === 'day') {
            $filename .= "_" . Carbon::parse($date)->format('Y-m-d');
        } else {
            $filename .= "_{$reportData['period']}_" . Carbon::parse($date)->format('Y-m-d');
        }

        return $pdf->download("{$filename}.pdf");
    }
}
