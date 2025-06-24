<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Services\PDFService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourierReportController extends Controller
{
    protected PDFService $pdfService;

    public function __construct(PDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function courierDeliveriesReport(Courier $courier, Request $request): \Illuminate\Http\Response
    {
        $this->authorizeOwner($courier);

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$startDate || !$endDate) {
            $startDate = \Carbon\Carbon::today()->format('Y-m-d');
            $endDate = \Carbon\Carbon::today()->format('Y-m-d');
        }

        $deliveriesQuery = $courier->deliveries();

        if ($startDate && $endDate) {
            $deliveriesQuery->byPeriod($startDate, $endDate);
        }

        $deliveries = $deliveriesQuery->with(['service', 'client', 'receipt'])->get();

        $pdf = $this->pdfService->generateCourierDeliveriesReport($courier, $deliveries, $startDate, $endDate);
        return $pdf->stream("courier-deliveries-report-{$courier->id}.pdf");
    }

    public function allCouriersDeliveriesReport(Request $request): \Illuminate\Http\Response
    {
        $this->authorizeOwner(new Courier());

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$startDate || !$endDate) {
            $startDate = \Carbon\Carbon::today()->format('Y-m-d');
            $endDate = \Carbon\Carbon::today()->format('Y-m-d');
        }

        $couriers = Courier::with([
            'deliveries' => function ($query) use ($startDate, $endDate) {
                $query->byPeriod($startDate, $endDate)
                    ->with(['service', 'client', 'receipt']);
            }
        ])->get();

        $pdf = $this->pdfService->generateAllCouriersDeliveriesReport($couriers, $startDate, $endDate);
        return $pdf->stream("all-couriers-deliveries-report.pdf");
    }

    private function authorizeOwner($model): void
    {
        abort_if(!Auth::user(), 403, 'No tienes permiso para acceder a este recurso.');
    }
}
