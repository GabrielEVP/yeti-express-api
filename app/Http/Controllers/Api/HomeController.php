<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Services\DashboardService;
use App\Utils\FormatDate;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct(private readonly FormatDate $dateFormatter, private readonly DashboardService $dashboardService)
    {
    }

    public function getDashboardStats(Request $request): JsonResponse
    {
        $period = $request->input('period', 'day');
        $date = $request->input('date', now()->toDateString());
        [$startDate, $endDate] = $this->dateFormatter->getPeriodDates($period, $date);

        $user = Auth::user();
        $stats = $this->dashboardService->getStatsByPeriod($user->id, $startDate, $endDate);

        $companyBills = (float)$user->companyBills()
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        return response()->json([
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_delivered' => $stats['total_delivered'],
            'total_invoiced' => $stats['total_invoiced'],
            'total_collected' => $stats['total_collected'],
            'total_company_bills' => $companyBills,
            'historical_delivered' => $this->dashboardService->getHistoricalDelivered($user->id, $period, $date),
            'historical_invoiced' => $this->dashboardService->getHistoricalInvoiced($user->id, $period, $date),
            'historical_balance' => $this->dashboardService->getHistoricalBalance($user->id, $period, $date)
        ]);
    }

    public function getCashRegisterReport(Request $request)
    {
        $period = $request->input('period', 'day');
        $date = $request->input('date', now()->toDateString());
        [$startDate, $endDate] = $this->dateFormatter->getPeriodDates($period, $date);

        $user = Auth::user();
        $periodLabels = [];
        $periodData = [];

        if ($period === 'day') {
            if ($request->has('date')) {
                $dayStart = Carbon::parse($date)->startOfDay()->toDateTimeString();
                $dayEnd = Carbon::parse($date)->endOfDay()->toDateTimeString();
                $dayLabel = Carbon::parse($date)->format('d/m/Y');

                $periodLabels[] = "Caja del día {$dayLabel}";
                $periodData[] = $this->generatePeriodData($user->id, $dayStart, $dayEnd);
            } else {
                $today = Carbon::today();
                $dayStart = $today->copy()->startOfDay()->toDateTimeString();
                $dayEnd = $today->copy()->endOfDay()->toDateTimeString();

                $periodLabels[] = "Caja de hoy " . $today->format('d/m/Y');
                $periodData[] = $this->generatePeriodData($user->id, $dayStart, $dayEnd);
            }
        } elseif ($period === 'week') {
            $currentDate = Carbon::parse($startDate);
            $endDateTime = Carbon::parse($endDate);

            while ($currentDate->lte($endDateTime)) {
                $dayStart = $currentDate->copy()->startOfDay()->toDateTimeString();
                $dayEnd = $currentDate->copy()->endOfDay()->toDateTimeString();
                $dayLabel = $currentDate->format('l d/m/Y'); // Lunes 01/01/2025

                $periodLabels[] = "Caja {$dayLabel}";
                $periodData[] = $this->generatePeriodData($user->id, $dayStart, $dayEnd);

                $currentDate->addDay();
            }
        } elseif ($period === 'month') {
            $currentDate = Carbon::parse($startDate);
            $endDateTime = Carbon::parse($endDate);
            $weekNumber = 1;

            while ($currentDate->lte($endDateTime)) {
                $weekStart = $currentDate->copy()->toDateTimeString();
                $weekEnd = min($currentDate->copy()->addDays(6), $endDateTime)->endOfDay()->toDateTimeString();

                $periodLabels[] = "Caja Semana {$weekNumber} ({$currentDate->format('d/m/Y')} - {$currentDate->copy()->addDays(6)->format('d/m/Y')})";
                $periodData[] = $this->generatePeriodData($user->id, $weekStart, $weekEnd);

                $currentDate->addDays(7);
                $weekNumber++;
            }
        } elseif ($period === 'year') {
            $currentDate = Carbon::parse($startDate);
            $endDateTime = Carbon::parse($endDate);

            while ($currentDate->lte($endDateTime) && $currentDate->year == Carbon::parse($startDate)->year) {
                $monthStart = $currentDate->copy()->startOfMonth()->toDateTimeString();
                $monthEnd = $currentDate->copy()->endOfMonth()->toDateTimeString();
                $monthLabel = $currentDate->format('F Y'); // Enero 2025

                $periodLabels[] = "Caja de {$monthLabel}";
                $periodData[] = $this->generatePeriodData($user->id, $monthStart, $monthEnd);

                $currentDate->addMonth();
            }
        } else {
            $periodLabels[] = "Caja del período {$period} ({$startDate} - {$endDate})";
            $periodData[] = $this->generatePeriodData($user->id, $startDate, $endDate);
        }

        $reportData = [
            'period' => $period,
            'date' => $date,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'period_labels' => $periodLabels,
            'period_data' => $periodData
        ];

        $pdf = app(\App\Services\PDFService::class)->generateCashRegisterReport($reportData);

        $filename = "caja";
        if ($period === 'day') {
            $filename .= "_" . Carbon::parse($date)->format('Y-m-d');
        } else {
            $filename .= "_{$period}_" . Carbon::parse($date)->format('Y-m-d');
        }

        return $pdf->download("{$filename}.pdf");
    }

    private function generatePeriodData(int $userId, string $startDate, string $endDate): array
    {
        $stats = $this->dashboardService->getStatsByPeriod($userId, $startDate, $endDate);

        $totalExpenses = (float)Auth::user()->companyBills()
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $balance = $stats['total_collected'] - $totalExpenses;

        $deliveries = $this->dashboardService->getCashRegisterDeliveries($userId, $startDate, $endDate);

        $deliveriesByStatus = $this->dashboardService->getDeliveriesByStatus($userId, $startDate, $endDate);

        $courierSummary = $this->dashboardService->getCourierDeliverySummary($userId, $startDate, $endDate);

        $clientDebtSummary = $this->dashboardService->getClientDebtSummary($userId, $startDate, $endDate);

        $clientPaymentSummary = $this->dashboardService->getClientPaymentSummary($userId, $startDate, $endDate);

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
            'courierSummary' => $courierSummary,
            'clientDebtSummary' => $clientDebtSummary,
            'clientPaymentSummary' => $clientPaymentSummary
        ];
    }
}
