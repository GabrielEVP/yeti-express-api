<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\DebtPayment;
use App\Models\CompanyBill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Utils\FormatDate;

class HomeController extends Controller
{

    private FormatDate $dateFormatter;

    public function __construct(FormatDate $dateFormatter)
    {
        $this->dateFormatter = $dateFormatter;
    }
    public function getDashboardStats(Request $request): JsonResponse
    {
        $period = $request->input('period', 'day');
        $date = $request->input('date', now()->toDateString());

        [$startDate, $endDate] = $this->dateFormatter->getPeriodDates($period, $date);

        $user = Auth::user();
        $stats = $this->getStatsByPeriod($user->id, $startDate, $endDate);

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
            'historical_delivered' => $this->getHistoricalDelivered($user->id, $period, $date),
            'historical_invoiced' => $this->getHistoricalInvoiced($user->id, $period, $date),
            'historical_balance' => $this->getHistoricalBalance($user->id, $period, $date)
        ], 200);
    }

    private function getStatsByPeriod($userId, $startDate, $endDate): array
    {
        return [
            'total_delivered' => $this->getTotalDelivered($userId, $startDate, $endDate),
            'total_invoiced' => $this->getTotalInvoiced($userId, $startDate, $endDate),
            'total_collected' => $this->getTotalCollected($userId, $startDate, $endDate)
        ];
    }

    private function getTotalDelivered($userId, $startDate, $endDate): int
    {
        return Delivery::where('user_id', $userId)
            ->where('status', 'delivered')
            ->whereBetween('date', [$startDate, $endDate])
            ->count();
    }

    private function getTotalInvoiced($userId, $startDate, $endDate): float
    {
        return (float) Delivery::where('user_id', $userId)
            ->where('status', 'delivered')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
    }

    private function getTotalCollected($userId, $startDate, $endDate): float
    {

        $fullPaymentsTotal = Delivery::query()
            ->select(DB::raw('SUM(amount) as total'))
            ->where('user_id', $userId)
            ->where('deliveries.payment_type', 'full')
            ->where('deliveries.payment_status', 'paid')
            ->whereBetween('date', [$startDate, $endDate])
            ->value('total') ?? 0;

        $partialPaymentsTotal = (float) DebtPayment::query()
            ->select(DB::raw('SUM(debt_payments.amount) as total'))
            ->join('debts', 'debt_payments.debt_id', '=', 'debts.id')
            ->join('deliveries', 'debts.delivery_id', '=', 'deliveries.id')
            ->where('deliveries.user_id', $userId)
            ->whereBetween('debt_payments.created_at', [$startDate, $endDate])
            ->value('total') ?? 0;

        return (float) $fullPaymentsTotal + (float) $partialPaymentsTotal;
    }


    private function getHistoricalDelivered($userId, string $period, string $date): array
    {
        [$startDate, $endDate] = $this->dateFormatter->getPeriodDates($period, $date);
        $requestDate = Carbon::parse($date);

        return Delivery::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($delivery) use ($period, $requestDate) {
                $deliveryDate = Carbon::parse($delivery->date);
                return $this->dateFormatter->formatDateLabel($deliveryDate, $period, $requestDate);
            })
            ->map(function ($group, $date) {
                return ['date' => $date, 'total' => $group->count()];
            })
            ->values()
            ->toArray();
    }

    private function getHistoricalInvoiced($userId, string $period, string $date): array
    {
        [$startDate, $endDate] = $this->dateFormatter->getPeriodDates($period, $date);
        $requestDate = Carbon::parse($date);

        return Delivery::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(function ($delivery) use ($period, $requestDate) {
                $deliveryDate = Carbon::parse($delivery->date);
                return $this->dateFormatter->formatDateLabel($deliveryDate, $period, $requestDate);
            })
            ->map(function ($group, $date) {
                return ['date' => $date, 'total' => (float) $group->sum('amount')];
            })
            ->values()
            ->toArray();
    }

    private function getHistoricalBalance($userId, string $period, string $date): array
    {
        [$startDate, $endDate] = $this->dateFormatter->getPeriodDates($period, $date);
        $requestDate = Carbon::parse($date);

        $fullPayments = Delivery::query()
            ->select('amount', 'date')
            ->where('user_id', $userId)
            ->where('payment_type', 'full')
            ->where('payment_status', 'paid')
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $debtPayments = DebtPayment::query()
            ->select('debt_payments.amount', 'debt_payments.created_at')
            ->join('debts', 'debt_payments.debt_id', '=', 'debts.id')
            ->join('deliveries', 'debts.delivery_id', '=', 'deliveries.id')
            ->where('deliveries.user_id', $userId)
            ->whereBetween('debt_payments.created_at', [$startDate, $endDate])
            ->get();

        $companyBills = CompanyBill::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $groupedFullPayments = $fullPayments->groupBy(function ($delivery) use ($period, $requestDate) {
            return $this->dateFormatter->formatDateLabel(Carbon::parse($delivery->date), $period, $requestDate);
        });

        $groupedPartialPayments = $debtPayments->groupBy(function ($payment) use ($period, $requestDate) {
            return $this->dateFormatter->formatDateLabel(Carbon::parse($payment->created_at), $period, $requestDate);
        });

        $groupedBills = $companyBills->groupBy(function ($bill) use ($period, $requestDate) {
            return $this->dateFormatter->formatDateLabel(Carbon::parse($bill->date), $period, $requestDate);
        });

        $allDates = array_unique(array_merge(
            $groupedFullPayments->keys()->toArray(),
            $groupedPartialPayments->keys()->toArray(),
            $groupedBills->keys()->toArray()
        ));

        return collect($allDates)->sort()->map(function ($dateKey) use ($groupedFullPayments, $groupedPartialPayments, $groupedBills) {
            $fulls = $groupedFullPayments->get($dateKey, collect());
            $partials = $groupedPartialPayments->get($dateKey, collect());
            $bills = $groupedBills->get($dateKey, collect());

            $totalCollected = (float) $fulls->sum('amount') + (float) $partials->sum('amount');
            $totalExpenses = (float) $bills->sum('amount');
            $balance = $totalCollected - $totalExpenses;

            return [
                'date' => $dateKey,
                'total_collected' => $totalCollected,
                'total_expenses' => $totalExpenses,
                'balance' => $balance
            ];
        })->values()->toArray();
    }

}