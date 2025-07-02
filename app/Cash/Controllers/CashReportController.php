<?php

namespace App\Cash\Controllers;

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
        try {
            $startDate = $request->get('start_date') ?? '';
            $endDate = $request->get('end_date') ?? '';

            $pdf = $this->cashReportService->generateCashRegisterReport($startDate, $endDate);
            return $pdf->stream("reporte-caja-detallado.pdf");
        } catch (\Exception $e) {
            return response([
                'error' => 'Error al generar el reporte de caja',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    public function simplifiedCashRegisterReport(Request $request): Response
    {
        try {
            $startDate = $request->get('start_date') ?? '';
            $endDate = $request->get('end_date') ?? '';

            $pdf = $this->cashReportService->generateSimplifiedCashRegisterReport($startDate, $endDate);
            return $pdf->stream("reporte-cajas-simplificado.pdf");
        } catch (\Exception $e) {
            return response([
                'error' => 'Error al generar el reporte de caja simplificado',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
}
