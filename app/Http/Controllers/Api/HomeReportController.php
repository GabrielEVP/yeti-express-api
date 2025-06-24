<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Services\DashboardService;
use App\Services\PDFService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeReportController extends Controller
{
    protected PDFService $pdfService;
    protected DashboardService $dashboardService;

    private function parseDate(string $date): \Carbon\Carbon
    {
        $date = trim($date);
        Carbon::setLocale('es');
        $parsedDate = Carbon::parse($date)->startOfDay();
        $parsedDate->locale('es');
        return $parsedDate;
    }

    public function __construct(PDFService $pdfService, DashboardService $dashboardService)
    {
        $this->pdfService = $pdfService;
        $this->dashboardService = $dashboardService;

        // Establecer locale español para todas las instancias de Carbon
        Carbon::setLocale('es');
        setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'es');
    }

    public function cashRegisterReport(Request $request): \Illuminate\Http\Response
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$startDate || !$endDate) {
            $startDate = \Carbon\Carbon::today()->format('Y-m-d');
            $endDate = \Carbon\Carbon::today()->format('Y-m-d');
        }

        $userId = auth()->id();

        $startDateTime = $this->parseDate($startDate);
        $endDateTime = $this->parseDate($endDate);

        $startDateTime = $startDateTime->startOfDay();
        $endDateTime = $endDateTime->endOfDay();

        $periodData = [];
        $periodLabels = [];

        $currentDate = $startDateTime->copy();
        $generalSummary = [
            'total_delivered' => 0,
            'total_invoiced' => 0,
            'total_collected' => 0,
            'total_expenses' => 0,
            'balance' => 0
        ];

        $hasData = false;

        while ($currentDate->lte($endDateTime)) {
            $dayStart = $currentDate->copy()->startOfDay()->toDateTimeString();
            $dayEnd = $currentDate->copy()->endOfDay()->toDateTimeString();

            // Formatear la fecha en formato español completo
            Carbon::setLocale('es');
            $dayLabel = $currentDate->isToday() ? 'Hoy' : $currentDate->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');

            $dailyData = $this->generateReportData($userId, $dayStart, $dayEnd);

            $hasDailyActivity =
                $dailyData['summary']['total_delivered'] > 0 ||
                $dailyData['summary']['total_invoiced'] > 0 ||
                $dailyData['summary']['total_collected'] > 0 ||
                $dailyData['summary']['total_expenses'] > 0 ||
                !empty($dailyData['deliveries']) ||
                !empty($dailyData['previousDayPayments']);

            if ($hasDailyActivity) {
                $hasData = true;
            }

            $generalSummary['total_delivered'] += $dailyData['summary']['total_delivered'];
            $generalSummary['total_invoiced'] += $dailyData['summary']['total_invoiced'];
            $generalSummary['total_collected'] += $dailyData['summary']['total_collected'];
            $generalSummary['total_expenses'] += $dailyData['summary']['total_expenses'];
            $generalSummary['balance'] += $dailyData['summary']['balance'];

            $periodData[] = $dailyData;
            $periodLabels[] = $dayLabel;

            $currentDate->addDay();
        }

        $reportData = [
            'period' => 'personalizado',
            'date' => now()->format('d-m-Yp'),
            'start_date' => $startDateTime->format('d-m-Y'),
            'end_date' => $endDateTime->format('d-m-Y'),
            'period_labels' => $periodLabels,
            'period_data' => $periodData,
            'general_summary' => $generalSummary
        ];

        $pdf = $this->pdfService->generateCashRegisterReport($reportData);
        return $pdf->stream("reporte-caja-detallado.pdf");
    }

    public function simplifiedCashRegisterReport(Request $request): \Illuminate\Http\Response
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$startDate || !$endDate) {
            $startDate = \Carbon\Carbon::today()->format('Y-m-d');
            $endDate = \Carbon\Carbon::today()->format('Y-m-d');
        }

        $userId = auth()->id();

        $startDateTime = $this->parseDate($startDate);
        $endDateTime = $this->parseDate($endDate);

        $startDateTime = $startDateTime->startOfDay();
        $endDateTime = $endDateTime->endOfDay();

        $dailyData = [];
        $totalSummary = [
            'total_delivered' => 0,
            'total_invoiced' => 0,
            'total_collected' => 0,
            'total_expenses' => 0,
            'total_balance' => 0
        ];

        $currentDate = $startDateTime->copy();
        $hasData = false; // Bandera para verificar si hay datos en el período
        $allDeliveries = [];

        while ($currentDate->lte($endDateTime)) {
            $dayStart = $currentDate->copy()->startOfDay()->toDateTimeString();
            $dayEnd = $currentDate->copy()->endOfDay()->toDateTimeString();

            // Usar formato español completo para la fecha
            Carbon::setLocale('es');
            $dayLabel = $currentDate->isToday() ? 'Hoy' : $currentDate->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');

            $stats = $this->dashboardService->getStatsByPeriod($userId, $dayStart, $dayEnd);

            $expenses = (float)auth()->user()->companyBills()
                ->whereBetween('date', [$dayStart, $dayEnd])
                ->sum('amount');

            $balance = $stats['total_collected'] - $expenses;

            // Obtener los deliveries del día para mostrar el detalle de pagos
            $dailyDeliveries = $this->dashboardService->getCashRegisterDeliveries($userId, $dayStart, $dayEnd);

            if ($stats['total_delivered'] > 0 || $stats['total_collected'] > 0 || $expenses > 0) {
                $hasData = true;
            }

            $dailyData[] = [
                'date' => $dayLabel,
                'delivered' => $stats['total_delivered'],
                'invoiced' => $stats['total_invoiced'],
                'collected' => $stats['total_collected'],
                'expenses' => $expenses,
                'balance' => $balance,
                'deliveries' => $dailyDeliveries
            ];

            $allDeliveries = array_merge($allDeliveries, $dailyDeliveries);

            $totalSummary['total_delivered'] += $stats['total_delivered'];
            $totalSummary['total_invoiced'] += $stats['total_invoiced'];
            $totalSummary['total_collected'] += $stats['total_collected'];
            $totalSummary['total_expenses'] += $expenses;
            $totalSummary['total_balance'] += $balance;

            $currentDate->addDay();
        }

        $reportData = [
            'start_date' => $startDateTime->format('Y-m-d'),
            'end_date' => $endDateTime->format('Y-m-d'),
            'daily_data' => $dailyData,
            'summary' => $totalSummary,
            'all_deliveries' => $allDeliveries
        ];

        $pdf = $this->pdfService->generateSimplifiedCashRegisterReport($reportData);
        return $pdf->stream("reporte-cajas-simplificado.pdf");
    }


    private function generateReportData(int $userId, string $startDate, string $endDate): array
    {
        $stats = $this->dashboardService->getStatsByPeriod($userId, $startDate, $endDate);

        $totalExpenses = (float)auth()->user()->companyBills()
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $balance = $stats['total_collected'] - $totalExpenses;
        $deliveries = $this->dashboardService->getCashRegisterDeliveries($userId, $startDate, $endDate);
        $deliveriesByStatus = $this->dashboardService->getDeliveriesByStatus($userId, $startDate, $endDate);

        $startDateObj = Carbon::parse($startDate);
        $endDateObj = Carbon::parse($endDate);
        $formattedStartDate = $startDateObj->startOfDay()->toDateTimeString();
        $formattedEndDate = $endDateObj->endOfDay()->toDateTimeString();

        $previousDayPayments = \App\Models\Delivery::with(['client', 'debt.payments'])
            ->where('user_id', $userId)
            ->where('date', '<=', $formattedStartDate)
            ->where(function ($query) use ($formattedStartDate, $formattedEndDate) {
                $query->WhereHas('debt.payments', function ($q) use ($formattedStartDate, $formattedEndDate) {
                    $q->whereBetween('created_at', [$formattedStartDate, $formattedEndDate]);
                });
            })
            ->get()
            ->map(function ($delivery) {
                $paidAmount = $delivery->debt->payments->sum('amount');
                $pendingAmount = max(0, $delivery->amount - $paidAmount);

                $paymentDetails = $delivery->debt->payments
                    ->sortBy(function ($payment) {
                        return $payment->created_at;
                    })
                    ->map(function ($payment) {
                        return [
                            'date' => $payment->date ? $payment->date->format('d/m/Y') : $payment->created_at->format('d/m/Y'),
                            'amount' => (float)$payment->amount,
                            'payment_method' => \App\Helpers\PaymentMethodTranslator::toSpanish($payment->method ?? 'Efectivo'),
                        ];
                    })->values()->toArray();

                return [
                    'number' => $delivery->number,
                    'date' => $delivery->date->format('d/m/Y'),
                    'client' => $delivery->client ? $delivery->client->legal_name : 'Sin cliente',
                    'total_amount' => (float)$delivery->amount,
                    'paid_amount' => (float)$paidAmount,
                    'pending_amount' => (float)$pendingAmount,
                    'payment_status' => $delivery->payment_status,
                    'payment_details' => $paymentDetails
                ];
            })->toArray();
        $courierSummary = $this->dashboardService->getCourierDeliverySummary($userId, $startDate, $endDate);

        $clientDebtSummary = $this->dashboardService->getClientDebtSummary($userId, $startDate, $endDate) ?? [];
        $clientPaymentSummary = $this->dashboardService->getClientPaymentSummary($userId, $startDate, $endDate) ?? [];

        return [
            'summary' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_delivered' => $stats['total_delivered'],
                'total_invoiced' => $stats['total_invoiced'],
                'total_collected' => $stats['total_collected'],
                'total_expenses' => $totalExpenses,
                'balance' => $balance,
            ],
            'deliveries' => $deliveries,
            'deliveriesByStatus' => $deliveriesByStatus,
            'previousDayPayments' => $previousDayPayments,
            'courierSummary' => $courierSummary,
            'clientDebtSummary' => $clientDebtSummary,
            'clientPaymentSummary' => $clientPaymentSummary
        ];
    }
}

