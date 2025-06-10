<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\CompanyBill;
use App\Models\DebtPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function getDashboardStats(Request $request): JsonResponse
    {
        $period = $request->input('period', 'day');
        $date = $request->input('date', now()->toDateString());

        $startDate = $this->getStartDate($period, $date);
        $endDate = $this->getEndDate($period, $date);

        $user = Auth::user();

        // Get deliveries for the period
        $deliveries = $user->deliveries()
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['service', 'service.bills'])
            ->get();

        // Calculate total invoiced amount
        $totalInvoiced = (float) $deliveries->sum('amount');

        // Get debt payments for the period
        $debtPayments = (float) DebtPayment::whereHas('debt', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        // Get company bills for the period
        $companyBills = (float) $user->companyBills()
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        // Calculate total earnings (total invoiced + debt payments - total expenses)
        $totalEarnings = $totalInvoiced + $debtPayments - $companyBills;

        // Get historical data
        $historicalData = $this->getHistoricalData($period, $date, $user);

        return response()->json([
            'period' => $period,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'deliveries' => [
                'total' => $deliveries->count(),
                'list' => $deliveries
            ],
            'total_invoiced' => $totalInvoiced,
            'debt_payments' => $debtPayments,
            'company_bills' => $companyBills,
            'total_earnings' => $totalEarnings,
            'cash_balance' => $totalEarnings,
            'historical_data' => $historicalData
        ], 200);
    }

    private function getHistoricalData(string $period, string $date, $user): array
    {
        $date = Carbon::parse($date);
        $format = match ($period) {
            'day' => 'H:i',
            'week' => 'D',
            'month' => 'd',
            'year' => 'M',
            default => 'H:i',
        };

        $groupBy = match ($period) {
            'day' => 'hour',
            'week' => 'day',
            'month' => 'day',
            'year' => 'month',
            default => 'hour',
        };

        $startDate = $this->getStartDate($period, $date->copy()->subDays(30)->toDateString());
        $endDate = $this->getEndDate($period, $date->toDateString());

        $deliveries = Delivery::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw("DATE_FORMAT(date, '%Y-%m-%d') as date"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date')
            ->get()
            ->map(function ($item) use ($format) {
                return [
                    'date' => Carbon::parse($item->date)->format($format),
                    'count' => $item->count,
                    'total' => (float) $item->total
                ];
            });

        return [
            'deliveries' => $deliveries
        ];
    }

    private function getStartDate(string $period, string $date): string
    {
        $date = Carbon::parse($date);

        return match ($period) {
            'day' => $date->startOfDay()->toDateString(),
            'week' => $date->startOfWeek()->toDateString(),
            'month' => $date->startOfMonth()->toDateString(),
            'year' => $date->startOfYear()->toDateString(),
            default => $date->startOfDay()->toDateString(),
        };
    }

    private function getEndDate(string $period, string $date): string
    {
        $date = Carbon::parse($date);

        return match ($period) {
            'day' => $date->endOfDay()->toDateString(),
            'week' => $date->endOfWeek()->toDateString(),
            'month' => $date->endOfMonth()->toDateString(),
            'year' => $date->endOfYear()->toDateString(),
            default => $date->endOfDay()->toDateString(),
        };
    }
}
