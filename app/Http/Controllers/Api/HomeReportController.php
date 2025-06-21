<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PDFService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeReportController extends Controller
{
    protected PDFService $pdfService;

    public function __construct(PDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function cashRegisterReport(Request $request): \Illuminate\Http\Response
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$startDate || !$endDate) {
            abort(400, 'Se requieren fechas de inicio y fin para generar el reporte.');
        }

        $reportData = [
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];

        $pdf = $this->pdfService->generateCashRegisterReport($reportData);
        return $pdf->stream("cash-register-report.pdf");
    }

    private function authorizeOwner($model): void
    {
        abort_if(!Auth::user(), 403, 'No tienes permiso para acceder a este recurso.');
    }
}
