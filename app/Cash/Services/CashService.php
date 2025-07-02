<?php

namespace App\Cash\Services;

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

    public function getDashboardStats(int $userId, string $period, string $date): array
    {
        try {
            Carbon::setLocale('es');

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

            $stats = $this->getStatsByPeriod($userId, $startDate, $endDate);

            $companyBills = (float)Auth::user()->companyBills()
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
            $historicalDelivered = $this->getHistoricalDelivered($userId, $period, $date);
            $historicalInvoiced = $this->getHistoricalInvoiced($userId, $period, $date);
            $historicalBalance = $this->getHistoricalBalance($userId, $period, $date);

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

            return [
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
            ];
        } catch (\Exception $e) {
            throw new \Exception('Error al cargar los datos del dashboard: ' . $e->getMessage());
        }
    }

    public function getCashRegisterReportData(string $period, string $date): array
    {
        [$startDate, $endDate] = $this->dateFormatter->getPeriodDates($period, $date);

        $userId = Auth::id();
        $periodLabels = [];
        $periodData = [];

        if ($period === 'day') {
            // Para período diario, simplemente mostrar 'Hoy'
            $dayStart = Carbon::parse($date)->startOfDay()->toDateTimeString();
            $dayEnd = Carbon::parse($date)->endOfDay()->toDateTimeString();

            $periodLabels[] = "Hoy";
            $periodData[] = $this->generatePeriodData($userId, $dayStart, $dayEnd);
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
                $periodData[] = $this->generatePeriodData($userId, $dayStart, $dayEnd);

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
                $periodData[] = $this->generatePeriodData($userId, $weekStart, $weekEnd);

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
                $periodData[] = $this->generatePeriodData($userId, $monthStart, $monthEnd);

                $currentDate->addMonth();
            }
        } else {
            $periodLabels[] = "Caja del período {$period} ({$startDate} - {$endDate})";
            $periodData[] = $this->generatePeriodData($userId, $startDate, $endDate);
        }

        $periodSpanish = match ($period) {
            'day' => 'dia',
            'week' => 'semana',
            'month' => 'mes',
            'year' => 'año',
            default => $period,
        };

        return [
            'period' => $periodSpanish,
            'date' => $date,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'period_labels' => $periodLabels,
            'period_data' => $periodData
        ];
    }

    private function generatePeriodData(int $userId, string $startDate, string $endDate): array
    {
        $stats = $this->getStatsByPeriod($userId, $startDate, $endDate);

        $totalExpenses = (float)Auth::user()->companyBills()
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $balance = $stats['total_collected'] - $totalExpenses;

        $deliveries = $this->getCashRegisterDeliveries($userId, $startDate, $endDate);

        $deliveriesByStatus = $this->getDeliveriesByStatus($userId, $startDate, $endDate);

        $courierSummary = $this->getCourierDeliverySummary($userId, $startDate, $endDate);

        $clientDebtSummary = $this->getClientDebtSummary($userId, $startDate, $endDate);

        $clientPaymentSummary = $this->getClientPaymentSummary($userId, $startDate, $endDate);

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

    // Methods from DashboardService
    private function safeFormatDate($date, string $default = ''): string
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

        if (
            !Carbon::canBeCreatedFromFormat($date, 'Y-m-d') &&
            !Carbon::canBeCreatedFromFormat($date, 'Y-m-d H:i:s')
        ) {
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
            ->where('status', 'delivered')
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
            ->where('status', 'delivered')
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
            ->where('status', 'delivered')
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

        $start = Carbon::parse($this->ensureDateFormat($startDate))->startOfDay();
        $end = Carbon::parse($this->ensureDateFormat($endDate))->endOfDay();

        $deliveries = Delivery::with(['debt.payments'])
            ->where('user_id', $userId)
            ->whereBetween('date', [$start, $end])
            ->whereIn('payment_status', ['paid', 'partial_paid'])
            ->get();

        return $deliveries->map(function ($delivery) {
            $payments = $delivery->debt && $delivery->debt->payments
                ? $delivery->debt->payments->map(function ($payment) {
                    return [
                        'date' => $payment->date->format('Y-m-d'),
                        'amount' => (float)$payment->amount,
                        'method' => $payment->payment_method,
                        'notes' => $payment->notes,
                    ];
                })->toArray()
                : [];

            return [
                'delivery_number' => $delivery->number,
                'delivery_date' => $delivery->date->format('Y-m-d'),
                'delivery_amount' => (float)$delivery->amount,
                'payment_status' => $delivery->payment_status,
                'payments' => $payments,
            ];
        })->toArray();
    }
}
