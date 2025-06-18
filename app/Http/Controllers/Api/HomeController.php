<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Utils\FormatDate;
use App\Http\Services\DashboardService;
use App\Http\Services\PDFService;
use Illuminate\Http\Response;
use Carbon\Carbon;

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

        $companyBills = (float) $user->companyBills()
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

        // Generar reportes separados por período
        if ($period === 'day') {
            // Si el período es por día, podemos mostrar el reporte de ese día específico
            if ($request->has('date')) {
                $dayStart = Carbon::parse($date)->startOfDay()->toDateTimeString();
                $dayEnd = Carbon::parse($date)->endOfDay()->toDateTimeString();
                $dayLabel = Carbon::parse($date)->format('d/m/Y');

                $periodLabels[] = "Caja del día {$dayLabel}";
                $periodData[] = $this->generatePeriodData($user->id, $dayStart, $dayEnd);
            } else {
                // Para el período actual, mostrar el día de hoy
                $today = Carbon::today();
                $dayStart = $today->copy()->startOfDay()->toDateTimeString();
                $dayEnd = $today->copy()->endOfDay()->toDateTimeString();

                $periodLabels[] = "Caja de hoy " . $today->format('d/m/Y');
                $periodData[] = $this->generatePeriodData($user->id, $dayStart, $dayEnd);
            }
        } elseif ($period === 'week') {
            // Si es semana, generar reporte para cada día de la semana
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
            // Si es mes, generar reporte para cada semana
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
            // Si es año, generar reporte para cada mes
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
            // Para cualquier otro período, usar el rango completo
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

    /**
     * Genera los datos para un período específico
     */
    private function generatePeriodData(int $userId, string $startDate, string $endDate): array
    {
        $stats = $this->dashboardService->getStatsByPeriod($userId, $startDate, $endDate);

        $totalExpenses = (float) Auth::user()->companyBills()
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $balance = $stats['total_collected'] - $totalExpenses;

        // Get all deliveries
        $deliveries = $this->dashboardService->getCashRegisterDeliveries($userId, $startDate, $endDate);

        // Get deliveries categorized by status
        $deliveriesByStatus = $this->dashboardService->getDeliveriesByStatus($userId, $startDate, $endDate);

        // Get courier summary with detailed deliveries
        $courierSummary = $this->dashboardService->getCourierDeliverySummary($userId, $startDate, $endDate);

        // Get client debt summary
        $clientDebtSummary = $this->dashboardService->getClientDebtSummary($userId, $startDate, $endDate);

        // Get client payment summary
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
