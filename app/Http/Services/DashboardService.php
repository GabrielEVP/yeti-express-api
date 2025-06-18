<?php

namespace App\Http\Services;

use App\Models\Delivery;
use App\Models\DebtPayment;
use App\Models\CompanyBill;
use App\Utils\FormatDate;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(private FormatDate $dateFormatter)
    {
    }

    public function getStatsByPeriod(int $userId, string $startDate, string $endDate): array
    {
        return [
            'total_delivered' => $this->getTotalDelivered($userId, $startDate, $endDate),
            'total_invoiced' => $this->getTotalInvoiced($userId, $startDate, $endDate),
            'total_collected' => $this->getTotalCollected($userId, $startDate, $endDate)
        ];
    }

    public function getTotalDelivered(int $userId, string $startDate, string $endDate): int
    {
        return Delivery::where('user_id', $userId)
            ->where('status', 'delivered')
            ->whereBetween('date', [$startDate, $endDate])
            ->count();
    }

    public function getTotalInvoiced(int $userId, string $startDate, string $endDate): float
    {
        return (float) Delivery::where('user_id', $userId)
            ->where('status', 'delivered')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
    }

    public function getTotalCollected(int $userId, string $startDate, string $endDate): float
    {
        $fullPaymentsTotal = Delivery::query()
            ->where('user_id', $userId)
            ->where('payment_type', 'full')
            ->where('payment_status', 'paid')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $partialPaymentsTotal = DebtPayment::query()
            ->join('debts', 'debt_payments.debt_id', '=', 'debts.id')
            ->join('deliveries', 'debts.delivery_id', '=', 'deliveries.id')
            ->where('deliveries.user_id', $userId)
            ->whereBetween('debt_payments.created_at', [$startDate, $endDate])
            ->sum('debt_payments.amount');

        return (float) $fullPaymentsTotal + (float) $partialPaymentsTotal;
    }

    public function getHistoricalDelivered(int $userId, string $period, string $date): array
    {
        [$startDate, $endDate] = $this->dateFormatter->getPeriodDates($period, $date);
        $requestDate = Carbon::parse($date);

        return Delivery::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(fn($delivery) => $this->dateFormatter->formatDateLabel(Carbon::parse($delivery->date), $period, $requestDate))
            ->map(fn($group, $date) => ['date' => $date, 'total' => $group->count()])
            ->values()
            ->toArray();
    }

    public function getHistoricalInvoiced(int $userId, string $period, string $date): array
    {
        [$startDate, $endDate] = $this->dateFormatter->getPeriodDates($period, $date);
        $requestDate = Carbon::parse($date);

        return Delivery::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(fn($delivery) => $this->dateFormatter->formatDateLabel(Carbon::parse($delivery->date), $period, $requestDate))
            ->map(fn($group, $date) => ['date' => $date, 'total' => (float) $group->sum('amount')])
            ->values()
            ->toArray();
    }

    public function getHistoricalBalance(int $userId, string $period, string $date): array
    {
        [$startDate, $endDate] = $this->dateFormatter->getPeriodDates($period, $date);
        $requestDate = Carbon::parse($date);

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

        $groupedFull = $fullPayments->groupBy(fn($p) => $this->dateFormatter->formatDateLabel(Carbon::parse($p->date), $period, $requestDate));
        $groupedPartial = $debtPayments->groupBy(fn($p) => $this->dateFormatter->formatDateLabel(Carbon::parse($p->created_at), $period, $requestDate));
        $groupedBills = $companyBills->groupBy(fn($b) => $this->dateFormatter->formatDateLabel(Carbon::parse($b->date), $period, $requestDate));

        $allDates = array_unique(array_merge(
            $groupedFull->keys()->toArray(),
            $groupedPartial->keys()->toArray(),
            $groupedBills->keys()->toArray()
        ));

        return collect($allDates)->sort()->map(function ($dateKey) use ($groupedFull, $groupedPartial, $groupedBills) {
            $full = $groupedFull->get($dateKey, collect());
            $partial = $groupedPartial->get($dateKey, collect());
            $bills = $groupedBills->get($dateKey, collect());

            $collected = $full->sum('amount') + $partial->sum('amount');
            $expenses = $bills->sum('amount');
            $balance = $collected - $expenses;

            return [
                'date' => $dateKey,
                'total_collected' => (float) $collected,
                'total_expenses' => (float) $expenses,
                'balance' => (float) $balance
            ];
        })->values()->toArray();
    }

    public function getCashRegisterDeliveries(int $userId, string $startDate, string $endDate): array
    {
        $deliveries = Delivery::with(['client', 'courier', 'service'])
            ->where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        return $deliveries->map(function ($delivery) {
            return [
                'number' => $delivery->number,
                'date' => $delivery->date->format('Y-m-d'),
                'client' => $delivery->client->name ?? 'N/A',
                'courier' => $delivery->courier->name ?? 'N/A',
                'service' => $delivery->service->name ?? 'N/A',
                'amount' => (float) $delivery->amount,
                'status' => $delivery->status,
                'payment_status' => $delivery->payment_status,
                'payment_type' => $delivery->payment_type
            ];
        })->toArray();
    }

    public function getDeliveriesByStatus(int $userId, string $startDate, string $endDate): array
    {
        $deliveries = Delivery::with(['client', 'courier', 'service'])
            ->where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $delivered = $deliveries->where('status', 'delivered')->values();
        $canceled = $deliveries->where('status', 'canceled')->values();
        $collected = $deliveries->where('payment_status', 'paid')->values();
        $uncollected = $deliveries->whereIn('payment_status', ['pending', 'partial_paid'])->values();

        return [
            'delivered' => $delivered->map(function ($delivery) {
                return [
                    'number' => $delivery->number,
                    'date' => $delivery->date->format('Y-m-d'),
                    'client' => $delivery->client->name ?? 'N/A',
                    'courier' => $delivery->courier->name ?? 'N/A',
                    'service' => $delivery->service->name ?? 'N/A',
                    'amount' => (float) $delivery->amount,
                    'payment_status' => $delivery->payment_status
                ];
            })->toArray(),
            'canceled' => $canceled->map(function ($delivery) {
                return [
                    'number' => $delivery->number,
                    'date' => $delivery->date->format('Y-m-d'),
                    'client' => $delivery->client->name ?? 'N/A',
                    'courier' => $delivery->courier->name ?? 'N/A',
                    'service' => $delivery->service->name ?? 'N/A',
                    'amount' => (float) $delivery->amount,
                    'cancellation_notes' => $delivery->cancellation_notes
                ];
            })->toArray(),
            'collected' => $collected->map(function ($delivery) {
                return [
                    'number' => $delivery->number,
                    'date' => $delivery->date->format('Y-m-d'),
                    'client' => $delivery->client->name ?? 'N/A',
                    'courier' => $delivery->courier->name ?? 'N/A',
                    'service' => $delivery->service->name ?? 'N/A',
                    'amount' => (float) $delivery->amount,
                    'payment_type' => $delivery->payment_type
                ];
            })->toArray(),
            'uncollected' => $uncollected->map(function ($delivery) {
                return [
                    'number' => $delivery->number,
                    'date' => $delivery->date->format('Y-m-d'),
                    'client' => $delivery->client->name ?? 'N/A',
                    'courier' => $delivery->courier->name ?? 'N/A',
                    'service' => $delivery->service->name ?? 'N/A',
                    'amount' => (float) $delivery->amount,
                    'payment_status' => $delivery->payment_status
                ];
            })->toArray()
        ];
    }

    public function getCourierDeliverySummary(int $userId, string $startDate, string $endDate): array
    {
        $deliveries = Delivery::with('courier')
            ->where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        return $deliveries->groupBy('courier_id')
            ->map(function ($group, $courierId) {
                $courier = $group->first()->courier;
                $courierName = $courier ? $courier->first_name . ' ' . $courier->last_name : 'Sin repartidor';

                $delivered = $group->where('status', 'delivered');
                $canceled = $group->where('status', 'canceled');

                return [
                    'courier' => $courierName,
                    'total_deliveries' => $group->count(),
                    'delivered_count' => $delivered->count(),
                    'delivered_amount' => (float) $delivered->sum('amount'),
                    'canceled_count' => $canceled->count(),
                    'canceled_amount' => (float) $canceled->sum('amount'),
                    'deliveries' => [
                        'delivered' => $delivered->map(function ($delivery) {
                            return [
                                'number' => $delivery->number,
                                'date' => $delivery->date->format('Y-m-d'),
                                'client' => $delivery->client->legal_name ?? 'N/A',
                                'amount' => (float) $delivery->amount,
                                'payment_status' => $delivery->payment_status
                            ];
                        })->values()->toArray(),
                        'canceled' => $canceled->map(function ($delivery) {
                            return [
                                'number' => $delivery->number,
                                'date' => $delivery->date->format('Y-m-d'),
                                'client' => $delivery->client->legal_name ?? 'N/A',
                                'amount' => (float) $delivery->amount,
                                'cancellation_notes' => $delivery->cancellation_notes ?? 'N/A'
                            ];
                        })->values()->toArray()
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
            ->map(function ($group, $clientId) {
                $client = $group->first()->client;
                $clientName = $client ? $client->legal_name : 'Cliente desconocido';
                $totalDebt = 0;

                foreach ($group as $delivery) {
                    if ($delivery->payment_status === 'pending') {
                        $totalDebt += $delivery->amount;
                    } else if ($delivery->payment_status === 'partial_paid' && $delivery->debt) {
                        $paid = $delivery->debt->payments->sum('amount');
                        $totalDebt += ($delivery->amount - $paid);
                    }
                }

                return [
                    'client' => $clientName,
                    'total_deliveries' => $group->count(),
                    'total_debt' => (float) $totalDebt,
                    'deliveries' => $group->map(function ($delivery) {
                        $pendingAmount = $delivery->amount;
                        $paidAmount = 0;
                        $paymentDetails = [];

                        if ($delivery->debt) {
                            $payments = $delivery->debt->payments;
                            $paidAmount = $payments->sum('amount');
                            $pendingAmount = $delivery->amount - $paidAmount;

                            $paymentDetails = $payments->map(function ($payment) {
                                return [
                                    'date' => $payment->date->format('Y-m-d'),
                                    'amount' => (float) $payment->amount,
                                    'payment_method' => $payment->payment_method,
                                    'notes' => $payment->notes
                                ];
                            })->toArray();
                        }

                        return [
                            'number' => $delivery->number,
                            'date' => $delivery->date->format('Y-m-d'),
                            'amount' => (float) $delivery->amount,
                            'paid_amount' => (float) $paidAmount,
                            'pending_amount' => (float) $pendingAmount,
                            'payment_status' => $delivery->payment_status,
                            'payment_details' => $paymentDetails
                        ];
                    })->toArray()
                ];
            })->values()->toArray();
    }

    public function getClientPaymentSummary(int $userId, string $startDate, string $endDate): array
    {
        // Obtener todos los deliveries pagados en el período
        $paidDeliveries = Delivery::with(['client', 'debt.payments'])
            ->where('user_id', $userId)
            ->where('payment_status', 'paid')
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // Obtener pagos parciales realizados en el período
        $partialPayments = DebtPayment::with(['debt.delivery.client'])
            ->whereHas('debt.delivery', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // Agrupar por cliente los deliveries completamente pagados
        $paidByClient = $paidDeliveries->groupBy(function($delivery) {
            return $delivery->client_id;
        });

        // Agrupar por cliente los pagos parciales
        $partialByClient = $partialPayments->groupBy(function($payment) {
            return $payment->debt->delivery->client_id;
        });

        // Unir los IDs de clientes
        $clientIds = collect(array_merge(
            $paidByClient->keys()->toArray(),
            $partialByClient->keys()->toArray()
        ))->unique();

        return $clientIds->map(function($clientId) use ($paidByClient, $partialByClient, $startDate, $endDate) {
            // Determinar el nombre del cliente
            $clientName = 'Cliente desconocido';

            if ($paidByClient->has($clientId) && $paidByClient[$clientId]->first()->client) {
                $clientName = $paidByClient[$clientId]->first()->client->legal_name;
            } elseif ($partialByClient->has($clientId) && $partialByClient[$clientId]->first()->debt->delivery->client) {
                $clientName = $partialByClient[$clientId]->first()->debt->delivery->client->legal_name;
            }

            // Pagos completos
            $fullPayments = $paidByClient->get($clientId, collect())->map(function($delivery) {
                return [
                    'number' => $delivery->number,
                    'date' => $delivery->date->format('Y-m-d'),
                    'payment_date' => $delivery->updated_at->format('Y-m-d'),
                    'amount' => (float) $delivery->amount,
                    'payment_type' => $delivery->payment_type,
                    'payment_status' => 'paid',
                    'is_full_payment' => true
                ];
            })->toArray();

            // Pagos parciales
            $partial = $partialByClient->get($clientId, collect())->map(function($payment) {
                $delivery = $payment->debt->delivery;

                return [
                    'number' => $delivery->number,
                    'date' => $delivery->date->format('Y-m-d'),
                    'payment_date' => $payment->date->format('Y-m-d'),
                    'amount' => (float) $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'delivery_amount' => (float) $delivery->amount,
                    'payment_status' => $delivery->payment_status,
                    'notes' => $payment->notes,
                    'is_full_payment' => false
                ];
            })->toArray();

            // Total pagado (pagos completos + parciales)
            $totalFullPaid = collect($fullPayments)->sum('amount');
            $totalPartialPaid = collect($partial)->sum('amount');

            return [
                'client' => $clientName,
                'total_paid' => (float) ($totalFullPaid + $totalPartialPaid),
                'full_payments_total' => (float) $totalFullPaid,
                'partial_payments_total' => (float) $totalPartialPaid,
                'full_payments_count' => count($fullPayments),
                'partial_payments_count' => count($partial),
                'full_payments' => $fullPayments,
                'partial_payments' => $partial
            ];
        })->values()->toArray();
    }

}
