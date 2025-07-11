<?php

namespace App\Cash\Services;

use App\Cash\DTO\CashRegisterReportDTO;
use App\Cash\DTO\DashboardStatsDTO;
use App\Cash\Utils\FormatDate;
use App\CompanyBIll\Models\CompanyBill;
use App\Debt\Models\DebtPayment;
use App\Delivery\Models\Delivery;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CashService
{
    public function __construct(private readonly FormatDate $dateFormatter)
    {
        Carbon::setLocale('es');
    }

    public function getDashboardStats(int $userId, string $period, string $date): DashboardStatsDTO
    {
        try {
            $date = $this->validateDate($date);
            [$startDate, $endDate] = $this->dateFormatter->getPeriodDates($period, $date);

            $stats = $this->getStatsByPeriod($userId, $startDate, $endDate);
            $companyBills = (float)Auth::user()->companyBills()
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('amount');

            return new DashboardStatsDTO([
                'period' => $this->getPeriodInSpanish($period),
                'period_label' => $this->getPeriodLabel($period),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'formatted_start_date' => $this->formatDisplayDate($startDate),
                'formatted_end_date' => $this->formatDisplayDate($endDate),
                'total_delivered' => $stats['total_delivered'],
                'total_invoiced' => $stats['total_invoiced'],
                'total_collected' => $stats['total_collected'],
                'total_company_bills' => $companyBills,
                'historical_delivered' => $this->getHistoricalData($userId, $period, $date, 'delivered'),
                'historical_invoiced' => $this->getHistoricalData($userId, $period, $date, 'invoiced'),
                'historical_balance' => $this->getHistoricalBalance($userId, $period, $date)
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Error al cargar los datos del dashboard: ' . $e->getMessage());
        }
    }

    public function getCashRegisterReportData(string $period, string $date): CashRegisterReportDTO
    {
        [$startDate, $endDate] = $this->dateFormatter->getPeriodDates($period, $date);
        $userId = Auth::id();

        [$periodLabels, $periodData] = $this->generatePeriodLabelsAndData($userId, $period, $startDate, $endDate);

        return new CashRegisterReportDTO([
            'period' => $this->getPeriodInSpanish($period),
            'date' => $date,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'period_labels' => $periodLabels,
            'period_data' => $periodData
        ]);
    }

    private function validateDate(string $date): string
    {
        try {
            if (!$date || !Carbon::canBeCreatedFromFormat($date, 'Y-m-d')) {
                return now()->toDateString();
            }
            return $date;
        } catch (\Exception $e) {
            \Log::warning("Fecha inválida, usando fecha actual: {$e->getMessage()}");
            return now()->toDateString();
        }
    }

    private function getPeriodInSpanish(string $period): string
    {
        return match ($period) {
            'day' => 'dia',
            'week' => 'semana',
            'month' => 'mes',
            'year' => 'año',
            default => $period,
        };
    }

    private function getPeriodLabel(string $period): string
    {
        return match ($period) {
            'day' => 'Día',
            'week' => 'Semana',
            'month' => 'Mes',
            'year' => 'Año',
            default => ucfirst($period),
        };
    }

    private function formatDisplayDate(string $date): string
    {
        $dateObj = Carbon::parse($date);
        return $dateObj->isSameDay(Carbon::today())
            ? 'Hoy'
            : $dateObj->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');
    }

    private function generatePeriodLabelsAndData(int $userId, string $period, string $startDate, string $endDate): array
    {
        $periodLabels = [];
        $periodData = [];

        if ($period === 'day') {
            $periodLabels[] = "Hoy";
            $periodData[] = $this->generatePeriodData($userId, $startDate, $endDate);
        } elseif ($period === 'week') {
            $currentDate = Carbon::parse($startDate);
            while ($currentDate->lte(Carbon::parse($endDate))) {
                $periodLabels[] = ucfirst($currentDate->locale('es')->dayName);
                $periodData[] = $this->generatePeriodData($userId,
                    $currentDate->copy()->startOfDay()->toDateTimeString(),
                    $currentDate->copy()->endOfDay()->toDateTimeString()
                );
                $currentDate->addDay();
            }
        } elseif ($period === 'month') {
            $currentDate = Carbon::parse($startDate);
            $weekNumber = 1;
            while ($currentDate->lte(Carbon::parse($endDate))) {
                $periodLabels[] = "Semana {$weekNumber}";
                $periodData[] = $this->generatePeriodData($userId,
                    $currentDate->copy()->toDateTimeString(),
                    min($currentDate->copy()->addDays(6), Carbon::parse($endDate))->endOfDay()->toDateTimeString()
                );
                $currentDate->addDays(7);
                $weekNumber++;
            }
        } elseif ($period === 'year') {
            $currentDate = Carbon::parse($startDate);
            while ($currentDate->lte(Carbon::parse($endDate)) && $currentDate->year == Carbon::parse($startDate)->year) {
                $periodLabels[] = ucfirst($currentDate->locale('es')->monthName);
                $periodData[] = $this->generatePeriodData($userId,
                    $currentDate->copy()->startOfMonth()->toDateTimeString(),
                    $currentDate->copy()->endOfMonth()->toDateTimeString()
                );
                $currentDate->addMonth();
            }
        }

        return [$periodLabels, $periodData];
    }

    private function generatePeriodData(int $userId, string $startDate, string $endDate): array
    {
        $stats = $this->getStatsByPeriod($userId, $startDate, $endDate);
        $totalExpenses = (float)Auth::user()->companyBills()
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        return [
            'summary' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_delivered' => $stats['total_delivered'],
                'total_invoiced' => $stats['total_invoiced'],
                'total_collected' => $stats['total_collected'],
                'total_expenses' => $totalExpenses,
                'balance' => $stats['total_collected'] - $totalExpenses,
            ],
            'deliveries' => $this->getCashRegisterDeliveries($userId, $startDate, $endDate),
            'deliveriesByStatus' => $this->getDeliveriesByStatus($userId, $startDate, $endDate),
            'courierSummary' => $this->getCourierDeliverySummary($userId, $startDate, $endDate),
            'clientDebtSummary' => $this->getClientDebtSummary($userId, $startDate, $endDate),
            'clientPaymentSummary' => $this->getClientPaymentSummary($userId, $startDate, $endDate)
        ];
    }

    private function ensureDateFormat($date): string
    {
        if ($date instanceof Carbon) {
            return $date->toDateTimeString();
        }

        if (is_string($date) && strpos($date, '/') !== false) {
            $parts = explode('/', trim($date));
            if (count($parts) === 3) {
                $date = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            }
        }

        try {
            return Carbon::parse($date)->toDateTimeString();
        } catch (\Exception $e) {
            \Log::error('Error formatting date: ' . $e->getMessage());
            return Carbon::now()->toDateTimeString();
        }
    }

    public function getStatsByPeriod(int $userId, $startDate, $endDate): array
    {
        $startFormatted = $this->ensureDateFormat($startDate);
        $endFormatted = $this->ensureDateFormat($endDate);

        $delivered = Delivery::where('user_id', $userId)
            ->where('status', 'delivered')
            ->whereBetween('date', [$startFormatted, $endFormatted]);

        $fullPayments = (float)$delivered->clone()
            ->where('payment_type', 'full')
            ->where('payment_status', 'paid')
            ->sum('amount');

        $partialPayments = (float)DebtPayment::join('debts', 'debt_payments.debt_id', '=', 'debts.id')
            ->join('deliveries', 'debts.delivery_id', '=', 'deliveries.id')
            ->where('deliveries.user_id', $userId)
            ->whereBetween('debt_payments.created_at', [$startFormatted, $endFormatted])
            ->sum('debt_payments.amount');

        return [
            'total_delivered' => $delivered->count(),
            'total_invoiced' => (float)$delivered->sum('amount'),
            'total_collected' => $fullPayments + $partialPayments
        ];
    }

    private function getHistoricalData(int $userId, string $period, string $date, string $type): array
    {
        [$startDate, $endDate] = $this->dateFormatter->getPeriodDates($period, $date);
        $deliveries = Delivery::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->get();

        if ($period === 'week') {
            $dayMapping = $this->createDayMapping($startDate, $endDate);
            return $deliveries->groupBy(function ($delivery) use ($dayMapping) {
                return $dayMapping[$delivery->date->toDateString()] ?? ucfirst($delivery->date->locale('es')->dayName);
            })->map(function ($group, $date) use ($type) {
                $total = $type === 'delivered' ? $group->count() : (float)$group->sum('amount');
                return ['date' => $date, 'total' => $total];
            })->values()->toArray();
        }

        return $deliveries->groupBy(function ($delivery) use ($period, $date) {
            return $this->dateFormatter->formatDateLabel(Carbon::parse($delivery->date), $period, Carbon::parse($date));
        })->map(function ($group, $date) use ($type) {
            $total = $type === 'delivered' ? $group->count() : (float)$group->sum('amount');
            return ['date' => $date, 'total' => $total];
        })->values()->toArray();
    }

    private function createDayMapping(string $startDate, string $endDate): array
    {
        $dayMapping = [];
        $currentDate = Carbon::parse($startDate);
        while ($currentDate->lte(Carbon::parse($endDate))) {
            $dayMapping[$currentDate->toDateString()] = ucfirst($currentDate->locale('es')->dayName);
            $currentDate->addDay();
        }
        return $dayMapping;
    }

    public function getHistoricalBalance(int $userId, string $period, string $date): array
    {
        [$startDate, $endDate] = $this->dateFormatter->getPeriodDates($period, $date);

        $fullPayments = Delivery::where('user_id', $userId)
            ->where('payment_type', 'full')
            ->where('payment_status', 'paid')
            ->whereBetween('date', [$startDate, $endDate])
            ->get(['amount', 'date']);

        $debtPayments = DebtPayment::join('debts', 'debt_payments.debt_id', '=', 'debts.id')
            ->join('deliveries', 'debts.delivery_id', '=', 'deliveries.id')
            ->where('deliveries.user_id', $userId)
            ->whereBetween('debt_payments.created_at', [$startDate, $endDate])
            ->get(['debt_payments.amount', 'debt_payments.created_at']);

        $companyBills = CompanyBill::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $dayMapping = $period === 'week' ? $this->createDayMapping($startDate, $endDate) : [];

        $groupedData = $this->groupPaymentsByPeriod($fullPayments, $debtPayments, $companyBills, $period, $date, $dayMapping);

        return collect($groupedData)->map(function ($dateKey) use ($fullPayments, $debtPayments, $companyBills, $period, $date, $dayMapping) {
            $collected = $this->calculateCollectedForDate($fullPayments, $debtPayments, $dateKey, $period, $date, $dayMapping);
            $expenses = $this->calculateExpensesForDate($companyBills, $dateKey, $period, $date, $dayMapping);

            return [
                'date' => $dateKey,
                'total_collected' => (float)$collected,
                'total_expenses' => (float)$expenses,
                'balance' => (float)($collected - $expenses)
            ];
        })->values()->toArray();
    }

    private function groupPaymentsByPeriod($fullPayments, $debtPayments, $companyBills, $period, $date, $dayMapping): array
    {
        $allDates = collect();

        foreach ([$fullPayments, $debtPayments, $companyBills] as $collection) {
            $dates = $collection->map(function ($item) use ($period, $date, $dayMapping) {
                $itemDate = isset($item->date) ? $item->date : $item->created_at;
                return $this->getDateGroupKey($itemDate, $period, $date, $dayMapping);
            });
            $allDates = $allDates->merge($dates);
        }

        return $allDates->unique()->sort()->toArray();
    }

    private function getDateGroupKey($date, $period, $requestDate, $dayMapping): string
    {
        $dateString = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        if ($period === 'week' && !empty($dayMapping)) {
            return $dayMapping[$dateString] ?? ucfirst(Carbon::parse($dateString)->locale('es')->dayName);
        }

        return $this->dateFormatter->formatDateLabel(Carbon::parse($date), $period, Carbon::parse($requestDate));
    }

    private function calculateCollectedForDate($fullPayments, $debtPayments, $dateKey, $period, $date, $dayMapping): float
    {
        $full = $fullPayments->filter(function ($p) use ($dateKey, $period, $date, $dayMapping) {
            return $this->getDateGroupKey($p->date, $period, $date, $dayMapping) === $dateKey;
        });

        $partial = $debtPayments->filter(function ($p) use ($dateKey, $period, $date, $dayMapping) {
            return $this->getDateGroupKey($p->created_at, $period, $date, $dayMapping) === $dateKey;
        });

        return $full->sum('amount') + $partial->sum('amount');
    }

    private function calculateExpensesForDate($companyBills, $dateKey, $period, $date, $dayMapping): float
    {
        return $companyBills->filter(function ($b) use ($dateKey, $period, $date, $dayMapping) {
            return $this->getDateGroupKey($b->date, $period, $date, $dayMapping) === $dateKey;
        })->sum('amount');
    }

    private function processDeliveryData($delivery): array
    {
        $paidAmount = 0;
        $pendingAmount = $delivery->amount;
        $paymentDetails = [];

        if ($delivery->payment_status === 'paid' && $delivery->payment_type === 'full') {
            $paidAmount = $delivery->amount;
            $pendingAmount = 0;
            $paymentDetails[] = [
                'date' => $delivery->updated_at->format('Y-m-d'),
                'amount' => (float)$delivery->amount,
                'payment_method' => 'Efectivo',
                'notes' => 'Pago completo'
            ];
        } elseif ($delivery->debt && $delivery->debt->payments->count() > 0) {
            $paidAmount = $delivery->debt->payments->sum('amount');
            $pendingAmount = max(0, $delivery->amount - $paidAmount);
            $paymentDetails = $delivery->debt->payments->map(function ($payment) {
                return [
                    'date' => ($payment->date ?? $payment->created_at)->format('Y-m-d'),
                    'amount' => (float)$payment->amount,
                    'payment_method' => $payment->method ?? 'Efectivo',
                    'notes' => $payment->notes ?? ''
                ];
            })->toArray();
        }

        return [
            'number' => $delivery->number,
            'date' => $delivery->date->format('Y-m-d'),
            'client' => $delivery->client_id
                ? $delivery->client->legal_name
                : $delivery->anonymousClient->legal_name,
            'is_anonymous_client' => !$delivery->client_id,
            'courier' => $delivery->courier ? $delivery->courier->first_name . ' ' . $delivery->courier->last_name : 'Sin repartidor',
            'service' => $delivery->service->name ?? 'Sin servicio',
            'total_amount' => (float)$delivery->amount,
            'paid_amount' => (float)$paidAmount,
            'pending_amount' => (float)$pendingAmount,
            'status' => $delivery->status,
            'payment_status' => $delivery->payment_status,
            'payment_type' => $delivery->payment_type,
            'payment_details' => $paymentDetails
        ];
    }

    public function getCashRegisterDeliveries(int $userId, string $startDate, string $endDate): array
    {
        $deliveries = Delivery::with(['client:id,legal_name', 'courier:id,first_name,last_name', 'service:id,name', 'debt.payments'])
            ->where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        return $deliveries->map(function ($delivery) {
            return $this->processDeliveryData($delivery);
        })->toArray();
    }

    public function getDeliveriesByStatus(int $userId, string $startDate, string $endDate): array
    {
        $deliveries = Delivery::with(['client:id,legal_name', 'courier:id,first_name,last_name', 'service:id,name', 'debt.payments'])
            ->where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        return [
            'delivered' => $deliveries->where('status', 'delivered')->map(fn($d) => $this->processDeliveryData($d))->values()->toArray(),
            'canceled' => $deliveries->where('status', 'cancelled')->map(fn($d) => $this->processDeliveryData($d))->values()->toArray(),
            'collected' => $deliveries->where('payment_status', 'paid')->map(fn($d) => $this->processDeliveryData($d))->values()->toArray(),
            'uncollected' => $deliveries->whereIn('payment_status', ['pending', 'partial_paid'])->map(fn($d) => $this->processDeliveryData($d))->values()->toArray()
        ];
    }

    public function getCourierDeliverySummary(int $userId, string $startDate, string $endDate): array
    {
        $deliveries = Delivery::with('courier')
            ->where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        return $deliveries->groupBy('courier_id')
            ->map(function ($group) {
                $courier = $group->first()->courier;
                $delivered = $group->where('status', 'delivered');
                $canceled = $group->where('status', 'cancelled');

                return [
                    'courier' => $courier ? $courier->first_name . ' ' . $courier->last_name : 'Sin repartidor',
                    'total_deliveries' => $group->count(),
                    'delivered_count' => $delivered->count(),
                    'delivered_amount' => (float)$delivered->sum('amount'),
                    'canceled_count' => $canceled->count(),
                    'canceled_amount' => (float)$canceled->sum('amount'),
                    'deliveries' => [
                        'delivered' => $delivered->map(fn($d) => $this->processDeliveryData($d))->values()->toArray(),
                        'canceled' => $canceled->map(fn($d) => $this->processDeliveryData($d))->values()->toArray()
                    ]
                ];
            })->values()->toArray();
    }

    public function getClientDebtSummary(int $userId, string $startDate, string $endDate): array
    {
        $debts = Delivery::with(['client', 'debt.payments'])
            ->where('user_id', $userId)
            ->whereIn('payment_status', ['pending', 'partial_paid'])
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        return $debts->groupBy('client_id')
            ->map(function ($group) {
                $client = $group->first()->client;
                $totalDebt = $group->sum(function ($delivery) {
                    if ($delivery->payment_status === 'pending') {
                        return $delivery->amount;
                    }
                    if ($delivery->payment_status === 'partial_paid' && $delivery->debt) {
                        return $delivery->amount - $delivery->debt->payments->sum('amount');
                    }
                    return 0;
                });

                return [
                    'client' => $client->legal_name ?? 'Cliente desconocido',
                    'total_deliveries' => $group->count(),
                    'total_debt' => (float)$totalDebt,
                    'deliveries' => $group->map(fn($d) => $this->processDeliveryData($d))->toArray()
                ];
            })->values()->toArray();
    }

    public function getClientPaymentSummary(int $userId, string $startDate, string $endDate): array
    {
        $deliveries = Delivery::with(['debt.payments'])
            ->where('user_id', $userId)
            ->whereBetween('date', [$this->ensureDateFormat($startDate), $this->ensureDateFormat($endDate)])
            ->whereIn('payment_status', ['paid', 'partial_paid'])
            ->get();

        return $deliveries->map(function ($delivery) {
            return [
                'delivery_number' => $delivery->number,
                'delivery_date' => $delivery->date->format('Y-m-d'),
                'delivery_amount' => (float)$delivery->amount,
                'payment_status' => $delivery->payment_status,
                'payments' => $delivery->debt && $delivery->debt->payments
                    ? $delivery->debt->payments->map(function ($payment) {
                        return [
                            'date' => $payment->date->format('Y-m-d'),
                            'amount' => (float)$payment->amount,
                            'method' => $payment->payment_method,
                            'notes' => $payment->notes,
                        ];
                    })->toArray()
                    : []
            ];
        })->toArray();
    }
}
