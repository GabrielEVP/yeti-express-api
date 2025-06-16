<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function getDashboardStats(Request $request): JsonResponse
    {
        $period = $request->input('period', 'day');
        $date = $request->input('date', now()->toDateString());

        $startDate = $this->getStartDate($period, $date);
        $endDate = $this->getEndDate($period, $date);

        $user = Auth::user();
        $stats = Delivery::getStatsByPeriod($user->id, $startDate, $endDate);
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
            'historical_delivered' => Delivery::getHistoricalDelivered($user->id, $period, $date),
            'historical_invoiced' => Delivery::getHistoricalInvoiced($user->id, $period, $date),
            'historical_balance' => Delivery::getHistoricalBalance($user->id, $period, $date)
        ], 200);
    }

    private function getStartDate(string $period, string $date): string
    {
        $date = Carbon::parse($date);

        return match ($period) {
            'day' => $date->startOfDay()->toDateTimeString(),
            'week' => $date->startOfWeek()->startOfDay()->toDateTimeString(),
            'month' => $date->startOfMonth()->startOfDay()->toDateTimeString(),
            'year' => $date->startOfYear()->startOfDay()->toDateTimeString(),
            default => $date->startOfDay()->toDateTimeString(),
        };
    }


    private function getEndDate(string $period, string $date): string
    {
        $date = Carbon::parse($date);

        return match ($period) {
            'day' => $date->endOfDay()->toDateTimeString(),
            'week' => $date->endOfWeek()->endOfDay()->toDateTimeString(),
            'month' => $date->endOfMonth()->endOfDay()->toDateTimeString(),
            'year' => $date->endOfYear()->endOfDay()->toDateTimeString(),
            default => $date->endOfDay()->toDateTimeString(),
        };
    }
}
