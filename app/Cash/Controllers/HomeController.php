<?php

namespace App\Http\Controllers\Api;

use App\Cash\Utils\FormatDate;
use App\Http\Controllers\Controller;
use App\Http\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct(private readonly FormatDate $dateFormatter, private readonly DashboardService $dashboardService)
    {
        // Configurar el idioma español para todas las instancias de Carbon
        Carbon::setLocale('es');
        setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'es');
    }

    public function getDashboardStats(Request $request): JsonResponse
    {
        try {
            Carbon::setLocale('es');
            $period = $request->input('period', 'day');
            $date = $request->input('date', now()->toDateString());

            // Asegurar que la fecha tenga un formato válido
            try {
                if (!$date || !Carbon::canBeCreatedFromFormat($date, 'Y-m-d')) {
                    $date = now()->toDateString();
                }
            } catch (\Exception $e) {
                \Log::warning("Fecha inválida, usando fecha actual: {$e->getMessage()}");
                $date = now()->toDateString();
            }

            [$startDate, $endDate] = $this->dateFormatter->getPeriodDates($period, $date);

            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            $stats = $this->dashboardService->getStatsByPeriod($user->id, $startDate, $endDate);

            $companyBills = (float)$user->companyBills()
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');

            // Format period display label in Spanish
            $periodLabel = match ($period) {
                'day' => 'Día',
                'week' => 'Semana',
                'month' => 'Mes',
                'year' => 'Año',
                default => ucfirst($period),
            };

            // Format start and end dates in Spanish
            $startDateObj = Carbon::parse($startDate);
            $endDateObj = Carbon::parse($endDate);
            $requestDateObj = Carbon::parse($date);

            // Formatear fechas en español
            $formattedStartDate = $startDateObj->isSameDay(Carbon::today())
                ? 'Hoy'
                : $startDateObj->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');

            $formattedEndDate = $endDateObj->isSameDay(Carbon::today())
                ? 'Hoy'
                : $endDateObj->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');

            // Mantener el periodo en inglés para la API pero traducir la etiqueta
            // En español utilizar la versión española del periodo
            $periodSpanish = match ($period) {
                'day' => 'dia',
                'week' => 'semana',
                'month' => 'mes',
                'year' => 'año',
                default => $period,
            };

            // Obtener los datos históricos
            $historicalDelivered = $this->dashboardService->getHistoricalDelivered($user->id, $period, $date);
            $historicalInvoiced = $this->dashboardService->getHistoricalInvoiced($user->id, $period, $date);
            $historicalBalance = $this->dashboardService->getHistoricalBalance($user->id, $period, $date);

            // Reformatear las etiquetas de fecha según el periodo
            if ($period === 'week') {
                // Determinar el primer día de la semana
                $weekStart = Carbon::parse($startDate);
                $dayNames = [];

                // Crear un array con los nombres de los días en español
                for ($i = 0; $i < 7; $i++) {
                    $currentDay = $weekStart->copy()->addDays($i);
                    $dayNames[] = ucfirst($currentDay->locale('es')->dayName);
                }

                // Asignar nombres de días a los datos históricos
                if (count($historicalDelivered) > 0 && count($dayNames) >= count($historicalDelivered)) {
                    foreach ($historicalDelivered as $key => $item) {
                        $historicalDelivered[$key]['date'] = $dayNames[$key % 7];
                    }
                }

                if (count($historicalInvoiced) > 0 && count($dayNames) >= count($historicalInvoiced)) {
                    foreach ($historicalInvoiced as $key => $item) {
                        $historicalInvoiced[$key]['date'] = $dayNames[$key % 7];
                    }
                }

                if (count($historicalBalance) > 0 && count($dayNames) >= count($historicalBalance)) {
                    foreach ($historicalBalance as $key => $item) {
                        $historicalBalance[$key]['date'] = $dayNames[$key % 7];
                    }
                }
            }

            return response()->json([
                'period' => $periodSpanish,
                'period_label' => $periodLabel,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'formatted_start_date' => $formattedStartDate,
                'formatted_end_date' => $formattedEndDate,
                'total_delivered' => $stats['total_delivered'],
                'total_invoiced' => $stats['total_invoiced'],
                'total_collected' => $stats['total_collected'],
                'total_company_bills' => $companyBills,
                'historical_delivered' => $historicalDelivered,
                'historical_invoiced' => $historicalInvoiced,
                'historical_balance' => $historicalBalance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cargar los datos del dashboard',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
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
            // Para período diario, simplemente mostrar 'Hoy'
            $dayStart = Carbon::parse($date)->startOfDay()->toDateTimeString();
            $dayEnd = Carbon::parse($date)->endOfDay()->toDateTimeString();

            $periodLabels[] = "Hoy";
            $periodData[] = $this->generatePeriodData($user->id, $dayStart, $dayEnd);
        } elseif ($period === 'week') {
            $currentDate = Carbon::parse($startDate);
            $endDateTime = Carbon::parse($endDate);

            while ($currentDate->lte($endDateTime)) {
                $dayStart = $currentDate->copy()->startOfDay()->toDateTimeString();
                $dayEnd = $currentDate->copy()->endOfDay()->toDateTimeString();

                // Mostrar solo el nombre del día en español sin prefijo
                Carbon::setLocale('es');
                $dayLabel = ucfirst($currentDate->locale('es')->dayName); // Lunes, Martes, etc.

                $periodLabels[] = $dayLabel;
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

                // Usar exactamente 'Semana 1', 'Semana 2', etc.
                $periodLabels[] = "Semana {$weekNumber}";
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

                // Mostrar solo el nombre del mes en español
                Carbon::setLocale('es');
                $monthLabel = ucfirst($currentDate->locale('es')->monthName); // Enero, Febrero, etc.

                $periodLabels[] = $monthLabel;
                $periodData[] = $this->generatePeriodData($user->id, $monthStart, $monthEnd);

                $currentDate->addMonth();
            }
        } else {
            $periodLabels[] = "Caja del período {$period} ({$startDate} - {$endDate})";
            $periodData[] = $this->generatePeriodData($user->id, $startDate, $endDate);
        }

        $periodSpanish = match ($period) {
            'day' => 'dia',
            'week' => 'semana',
            'month' => 'mes',
            'year' => 'año',
            default => $period,
        };

        $reportData = [
            'period' => $periodSpanish,
            'date' => $date,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'period_labels' => $periodLabels,
            'period_data' => $periodData
        ];

        $pdf = app(\App\Cash\Services\PDFService::class)->generateCashRegisterReport($reportData);

        $filename = "caja";
        if ($period === 'day') {
            $filename .= "_" . Carbon::parse($date)->format('Y-m-d');
        } else {
            $filename .= "_{$periodSpanish}_" . Carbon::parse($date)->format('Y-m-d');
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
