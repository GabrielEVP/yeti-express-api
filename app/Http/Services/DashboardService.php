<?php

namespace App\Http\Services;

use App\Models\CompanyBill;
use App\Models\DebtPayment;
use App\Models\Delivery;
use App\Utils\FormatDate;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(private FormatDate $dateFormatter)
    {
        Carbon::setLocale('es');
    }

    public function safeFormatDate($date, string $default = ''): string
    {
        if (empty($date)) {
            return $default;
        }

        if ($date instanceof Carbon) {
            return $date->format('Y-m-d');
        }

        if (!Carbon::hasFormat($date, 'Y-m-d') && !Carbon::hasFormat($date, 'Y-m-d H:i:s')) {
            \Log::warning("Invalid date format in safeFormatDate: {$date}");
            return $default;
        }

        return Carbon::parse($date)->format('Y-m-d');
    }

    private function ensureDateFormat($date): string
    {
        if ($date instanceof Carbon) {
            return $date->toDateTimeString();
        }

        if (is_string($date)) {
            $date = trim($date);

            if (strpos($date, '/') !== false) {
                $parts = explode('/', $date);
                if (count($parts) === 3) {
                    $date = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                }
            }
        }

        if (!is_string($date) && !is_object($date) && !is_numeric($date)) {
            \Log::error('Invalid date format: not convertible to date');
            return Carbon::now()->toDateTimeString();
        }

        if (!Carbon::canBeCreatedFromFormat($date, 'Y-m-d') &&
            !Carbon::canBeCreatedFromFormat($date, 'Y-m-d H:i:s')) {
            \Log::error('Error formatting date: ' . (is_string($date) ? $date : 'not a string'));
            return Carbon::now()->toDateTimeString();
        }

        return Carbon::parse($date)->toDateTimeString();
    }

    public function getStatsByPeriod(int $userId, $startDate, $endDate): array
    {
        $startFormatted = $this->ensureDateFormat($startDate);
        $endFormatted = $this->ensureDateFormat($endDate);

        return [
            'total_delivered' => $this->getTotalDelivered($userId, $startFormatted, $endFormatted),
            'total_invoiced' => $this->getTotalInvoiced($userId, $startFormatted, $endFormatted),
            'total_collected' => $this->getTotalCollected($userId, $startFormatted, $endFormatted)
        ];
    }

    public function getTotalDelivered(int $userId, $startDate, $endDate): int
    {
        $startFormatted = $this->ensureDateFormat($startDate);
        $endFormatted = $this->ensureDateFormat($endDate);

        return Delivery::where('user_id', $userId)
            ->where('status', 'delivered')
            ->whereBetween('date', [$startFormatted, $endFormatted])
            ->count();
    }

    public function getTotalInvoiced(int $userId, $startDate, $endDate): float
    {
        $startFormatted = $this->ensureDateFormat($startDate);
        $endFormatted = $this->ensureDateFormat($endDate);

        return (float)Delivery::where('user_id', $userId)
            ->where('status', 'delivered')
            ->whereBetween('date', [$startFormatted, $endFormatted])
            ->sum('amount');
    }

    public function getTotalCollected(int $userId, $startDate, $endDate): float
    {
        $startFormatted = $this->ensureDateFormat($startDate);
        $endFormatted = $this->ensureDateFormat($endDate);

        $fullPaymentsTotal = Delivery::query()
            ->where('user_id', $userId)
            ->where('payment_type', 'full')
            ->where('payment_status', 'paid')
            ->whereBetween('date', [$startFormatted, $endFormatted])
            ->sum('amount');

        $partialPaymentsTotal = DebtPayment::query()
            ->join('debts', 'debt_payments.debt_id', '=', 'debts.id')
            ->join('deliveries', 'debts.delivery_id', '=', 'deliveries.id')
            ->where('deliveries.user_id', $userId)
            ->whereBetween('debt_payments.created_at', [$startFormatted, $endFormatted])
            ->sum('debt_payments.amount');

        return (float)$fullPaymentsTotal + (float)$partialPaymentsTotal;
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
            ->map(fn($group, $date) => ['date' => $date, 'total' => (float)$group->sum('amount')])
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
                'total_collected' => (float)$collected,
                'total_expenses' => (float)$expenses,
                'balance' => (float)$balance
            ];
        })->values()->toArray();
    }

    public function getCashRegisterDeliveries(int $userId, string $startDate, string $endDate): array
    {
        $deliveries = Delivery::with(['client:id,legal_name', 'courier:id,first_name,last_name', 'service:id,name', 'debt.payments'])
            ->where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        return $deliveries->map(function ($delivery) {
            $paidAmount = 0;
            $pendingAmount = $delivery->amount;
            $paymentDetails = [];

            if ($delivery->payment_status === 'paid' && $delivery->payment_type === 'full') {
                // Pago completo
                $paidAmount = $delivery->amount;
                $pendingAmount = 0;

                // Agregar un detalle de pago para pagos completos
                $paymentDetails[] = [
                    'date' => $delivery->updated_at->format('Y-m-d'),
                    'amount' => (float)$delivery->amount,
                    'payment_method' => 'Efectivo',  // Default para pagos completos
                    'notes' => 'Pago completo'
                ];
            } elseif ($delivery->debt) {
                // Tiene deuda con pagos parciales
                if ($delivery->debt->payments && $delivery->debt->payments->count() > 0) {
                    $paidAmount = $delivery->debt->payments->sum('amount');
                    $pendingAmount = max(0, $delivery->amount - $paidAmount);

                    // Asegurarse de incluir TODOS los pagos ordenados por fecha
                    $paymentDetails = $delivery->debt->payments
                        ->sortBy(function ($payment) {
                            return $payment->date ?? $payment->created_at;
                        })
                        ->map(function ($payment) {
                            return [
                                'date' => $payment->date ? $payment->date->format('Y-m-d') : $payment->created_at->format('Y-m-d'),
                                'amount' => (float)$payment->amount,
                                'payment_method' => $payment->method ?? 'Efectivo',
                                'notes' => $payment->notes ?? ''
                            ];
                        })->values()->toArray();
                }
            }

            return [
                'number' => $delivery->number,
                'date' => $this->safeFormatDate($delivery->date, 'N/A'),
                'client' => $delivery->client ? $delivery->client->legal_name : 'Sin cliente',
                'courier' => $delivery->courier ? $delivery->courier->first_name . ' ' . $delivery->courier->last_name : 'Sin repartidor',
                'service' => $delivery->service ? $delivery->service->name : 'Sin servicio',
                'total_amount' => (float)$delivery->amount,
                'amount' => (float)$delivery->amount,  // Mantener para compatibilidad con vistas existentes
                'paid_amount' => (float)$paidAmount,
                'pending_amount' => (float)$pendingAmount,
                'status' => $delivery->status,
                'payment_status' => $delivery->payment_status,
                'payment_type' => $delivery->payment_type,
                'payment_details' => $paymentDetails
            ];
        })->toArray();
    }

    public function getDeliveriesByStatus(int $userId, string $startDate, string $endDate): array
    {
        $deliveries = Delivery::with(['client:id,legal_name', 'courier:id,first_name,last_name', 'service:id,name', 'debt.payments'])
            ->where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // Procesamos las entregas para asegurarnos que cada una tenga información detallada de sus pagos
        $delivered = $deliveries->where('status', 'delivered')->values();
        $canceled = $deliveries->where('status', 'cancelled')->values();
        $collected = $deliveries->where('payment_status', 'paid')->values();
        $uncollected = $deliveries->whereIn('payment_status', ['pending', 'partial_paid'])->values();

        return [
            'delivered' => $delivered->map(function ($delivery) {
                $paidAmount = 0;
                $pendingAmount = $delivery->amount;

                if ($delivery->payment_status === 'paid' && $delivery->payment_type === 'full') {
                    $paidAmount = $delivery->amount;
                    $pendingAmount = 0;
                } elseif ($delivery->debt) {
                    $paidAmount = $delivery->debt->payments->sum('amount');
                    $pendingAmount = max(0, $delivery->amount - $paidAmount);
                }

                return [
                    'number' => $delivery->number,
                    'date' => $delivery->date->format('Y-m-d'),
                    'client' => $delivery->client ? $delivery->client->legal_name : 'Sin cliente',
                    'courier' => $delivery->courier ? $delivery->courier->first_name . ' ' . $delivery->courier->last_name : 'Sin repartidor',
                    'service' => $delivery->service ? $delivery->service->name : 'Sin servicio',
                    'total_amount' => (float)$delivery->amount,
                    'amount' => (float)$delivery->amount,  // Mantener para compatibilidad con vistas existentes
                    'paid_amount' => (float)$paidAmount,
                    'pending_amount' => (float)$pendingAmount,
                    'payment_status' => $delivery->payment_status,
                    'payment_type' => $delivery->payment_type
                ];
            })->toArray(),
            'canceled' => $canceled->map(function ($delivery) {
                return [
                    'number' => $delivery->number,
                    'date' => $delivery->date->format('Y-m-d'),
                    'client' => $delivery->client ? $delivery->client->legal_name : 'Sin cliente',
                    'courier' => $delivery->courier ? $delivery->courier->first_name . ' ' . $delivery->courier->last_name : 'Sin repartidor',
                    'service' => $delivery->service->name ?? 'N/A',
                    'amount' => (float)$delivery->amount,
                    'cancellation_notes' => $delivery->cancellation_notes
                ];
            })->toArray(),
            'collected' => $collected->map(function ($delivery) {
                $paidAmount = 0;
                $paymentDetails = [];

                if ($delivery->payment_type === 'full') {
                    $paidAmount = $delivery->amount;
                } elseif ($delivery->debt) {
                    $paidAmount = $delivery->debt->payments->sum('amount');

                    $paymentDetails = $delivery->debt->payments->map(function ($payment) {
                        return [
                            'date' => $payment->date ? $payment->date->format('Y-m-d') : $payment->created_at->format('Y-m-d'),
                            'amount' => (float)$payment->amount,
                            'payment_method' => $payment->method ?? 'No especificado',
                            'notes' => $payment->notes ?? ''
                        ];
                    })->toArray();
                }

                return [
                    'number' => $delivery->number,
                    'date' => $delivery->date->format('Y-m-d'),
                    'client' => $delivery->client ? $delivery->client->legal_name : 'Sin cliente',
                    'courier' => $delivery->courier ? $delivery->courier->first_name . ' ' . $delivery->courier->last_name : 'Sin repartidor',
                    'service' => $delivery->service->name ?? 'N/A',
                    'total_amount' => (float)$delivery->amount,
                    'amount' => (float)$delivery->amount,  // Mantener para compatibilidad con vistas existentes
                    'paid_amount' => (float)$paidAmount,
                    'payment_type' => $delivery->payment_type,
                    'payment_details' => $paymentDetails
                ];
            })->toArray(),
            'uncollected' => $uncollected->map(function ($delivery) {
                $paidAmount = 0;
                $pendingAmount = $delivery->amount;
                $paymentDetails = [];

                if ($delivery->debt) {
                    $paidAmount = $delivery->debt->payments->sum('amount');
                    $pendingAmount = max(0, $delivery->amount - $paidAmount);

                    $paymentDetails = $delivery->debt->payments->map(function ($payment) {
                        return [
                            'date' => $payment->date ? $payment->date->format('Y-m-d') : $payment->created_at->format('Y-m-d'),
                            'amount' => (float)$payment->amount,
                            'payment_method' => $payment->method ?? 'No especificado',
                            'notes' => $payment->notes ?? ''
                        ];
                    })->toArray();
                }

                return [
                    'number' => $delivery->number,
                    'date' => $delivery->date->format('Y-m-d'),
                    'client' => $delivery->client ? $delivery->client->legal_name : 'Sin cliente',
                    'courier' => $delivery->courier ? $delivery->courier->first_name . ' ' . $delivery->courier->last_name : 'Sin repartidor',
                    'service' => $delivery->service->name ?? 'N/A',
                    'total_amount' => (float)$delivery->amount,
                    'amount' => (float)$delivery->amount,  // Mantener para compatibilidad con vistas existentes
                    'paid_amount' => (float)$paidAmount,
                    'pending_amount' => (float)$pendingAmount,
                    'payment_status' => $delivery->payment_status,
                    'payment_details' => $paymentDetails
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
                $canceled = $group->where('status', 'cancelled');

                return [
                    'courier' => $courierName,
                    'total_deliveries' => $group->count(),
                    'delivered_count' => $delivered->count(),
                    'delivered_amount' => (float)$delivered->sum('amount'),
                    'canceled_count' => $canceled->count(),
                    'canceled_amount' => (float)$canceled->sum('amount'),
                    'deliveries' => [
                        'delivered' => $delivered->map(function ($delivery) {
                            $paidAmount = 0;
                            $pendingAmount = $delivery->amount;

                            if ($delivery->payment_status === 'paid' && $delivery->payment_type === 'full') {
                                $paidAmount = $delivery->amount;
                                $pendingAmount = 0;
                            } elseif ($delivery->debt) {
                                $paidAmount = $delivery->debt->payments->sum('amount');
                                $pendingAmount = max(0, $delivery->amount - $paidAmount);
                            }

                            return [
                                'number' => $delivery->number,
                                'date' => $delivery->date->format('Y-m-d'),
                                'client' => $delivery->client->legal_name ?? 'N/A',
                                'total_amount' => (float)$delivery->amount,
                                'amount' => (float)$delivery->amount,  // Mantener para compatibilidad con vistas existentes
                                'paid_amount' => (float)$paidAmount,
                                'pending_amount' => (float)$pendingAmount,
                                'payment_status' => $delivery->payment_status
                            ];
                        })->values()->toArray(),
                        'canceled' => $canceled->map(function ($delivery) {
                            return [
                                'number' => $delivery->number,
                                'date' => $delivery->date->format('Y-m-d'),
                                'client' => $delivery->client->legal_name ?? 'N/A',
                                'amount' => (float)$delivery->amount,
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
                    'total_debt' => (float)$totalDebt,
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
                                    'amount' => (float)$payment->amount,
                                    'payment_method' => $payment->payment_method,
                                    'notes' => $payment->notes
                                ];
                            })->toArray();
                        }

                        return [
                            'number' => $delivery->number,
                            'date' => $delivery->date->format('Y-m-d'),
                            'amount' => (float)$delivery->amount,
                            'paid_amount' => (float)$paidAmount,
                            'pending_amount' => (float)$pendingAmount,
                            'payment_status' => $delivery->payment_status,
                            'payment_details' => $paymentDetails
                        ];
                    })->toArray()
                ];
            })->values()->toArray();
    }

    public function getClientPaymentSummary(int $userId, string $startDate, string $endDate): array
    {
        $paidDeliveries = Delivery::with(['client', 'debt.payments'])
            ->where('user_id', $userId)
            ->whereIn('payment_status', ['paid', 'partial_paid']) // Modificado para incluir pagos parciales
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        if (str_contains($startDate, '/')) {
            $parts = explode('/', trim($startDate));
            if (count($parts) === 3) {
                $startDate = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            }
        }

        if (str_contains($endDate, '/')) {
            $parts = explode('/', trim($endDate));
            if (count($parts) === 3) {
                $endDate = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            }
        }

        $startFormatted = $this->ensureDateFormat($startDate);
        $endFormatted = $this->ensureDateFormat($endDate);
        $startDateObj = Carbon::parse($startFormatted)->startOfDay();
        $endDateObj = Carbon::parse($endFormatted)->endOfDay();

        $partialPayments = DebtPayment::with(['debt.delivery.client'])
            ->whereHas('debt.delivery', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where(function ($query) use ($startDateObj, $endDateObj) {
                $query->whereBetween('date', [$startDateObj, $endDateObj])
                    ->orWhereBetween('created_at', [$startDateObj, $endDateObj]);
            })
            ->get();

        $paidByClient = $paidDeliveries->groupBy(function ($delivery) {
            return $delivery->client_id;
        });

        $partialByClient = $partialPayments->groupBy(function ($payment) {
            if ($payment->debt && $payment->debt->delivery && $payment->debt->delivery->client_id) {
                return $payment->debt->delivery->client_id;
            }
            return 0;
        });

        $clientIds = collect(array_merge(
            $paidByClient->keys()->toArray(),
            $partialByClient->keys()->toArray()
        ))->unique();

        return $clientIds->map(function ($clientId) use ($paidByClient, $partialByClient, $startDate, $endDate) {
            $clientName = 'Cliente desconocido';

            if ($paidByClient->has($clientId) && $paidByClient[$clientId]->first()->client) {
                $clientName = $paidByClient[$clientId]->first()->client->legal_name;
            } elseif ($partialByClient->has($clientId) && $partialByClient[$clientId]->first()->debt->delivery->client) {
                $clientName = $partialByClient[$clientId]->first()->debt->delivery->client->legal_name;
            }

            $fullPayments = $paidByClient->get($clientId, collect())->map(function ($delivery) {
                // Determinamos si es un pago completo o parcial
                $isFullPayment = $delivery->payment_status === 'paid';
                $paidAmount = $isFullPayment ? $delivery->amount :
                    ($delivery->debt ? $delivery->debt->payments->sum('amount') : 0);

                return [
                    'number' => $delivery->number,
                    'date' => $delivery->date->format('Y-m-d'),
                    'payment_date' => $delivery->updated_at->format('Y-m-d'),
                    'amount' => (float)$paidAmount,
                    'payment_type' => $delivery->payment_type,
                    'payment_status' => $delivery->payment_status,
                    'is_full_payment' => $isFullPayment
                ];
            })->toArray();

            $partial = $partialByClient->get($clientId, collect())->map(function ($payment) {
                $delivery = $payment->debt->delivery;

                return [
                    'number' => $delivery->number,
                    'date' => $delivery->date->format('Y-m-d'),
                    'payment_date' => $payment->date->format('Y-m-d'),
                    'amount' => (float)$payment->amount,
                    'payment_method' => $payment->payment_method,
                    'delivery_amount' => (float)$delivery->amount,
                    'payment_status' => $delivery->payment_status,
                    'notes' => $payment->notes,
                    'is_full_payment' => false
                ];
            })->toArray();

            $totalFullPaid = collect($fullPayments)->sum('amount');
            $totalPartialPaid = collect($partial)->sum('amount');

            return [
                'client' => $clientName,
                'total_paid' => (float)($totalFullPaid + $totalPartialPaid),
                'full_payments_total' => (float)$totalFullPaid,
                'partial_payments_total' => (float)$totalPartialPaid,
                'full_payments_count' => count($fullPayments),
                'partial_payments_count' => count($partial),
                'full_payments' => $fullPayments,
                'partial_payments' => $partial
            ];
        })->values()->toArray();
    }


}
