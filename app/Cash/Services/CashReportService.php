<?php

namespace App\Cash\Services;

use App\Cash\DomPDF\DomPDFCash;
use App\CompanyBill\Helpers\PaymentMethodTranslator;
use App\Delivery\Models\Delivery;
use App\Shared\Services\AuthHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CashReportService
{
    public function __construct(
        private readonly DomPDFCash  $pdfService,
        private readonly CashService $cashService
    )
    {
        Carbon::setLocale('es');
        setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'es');
    }

    public function parseDate(string $date): Carbon
    {
        $date = trim($date);
        Carbon::setLocale('es');
        $parsedDate = Carbon::parse($date)->startOfDay();
        $parsedDate->locale('es');
        return $parsedDate;
    }

    public function generateCashRegisterReport(string $startDate, string $endDate): \Barryvdh\DomPDF\PDF
    {
        if (!$startDate || !$endDate) {
            $startDate = Carbon::today()->format('Y-m-d');
            $endDate = Carbon::today()->format('Y-m-d');
        }

        $userId = AuthHelper::getUserId();

        $startDateTime = $this->parseDate($startDate);
        $endDateTime = $this->parseDate($endDate);

        $startDateTime = $startDateTime->startOfDay();
        $endDateTime = $endDateTime->endOfDay();

        $periodData = [];
        $periodLabels = [];

        $currentDate = $startDateTime->copy();
        $generalSummary = [
            'total_delivered' => 0,
            'total_invoiced' => 0,
            'total_collected' => 0,
            'total_expenses' => 0,
            'balance' => 0
        ];

        $hasData = false;

        while ($currentDate->lte($endDateTime)) {
            $dayStart = $currentDate->copy()->startOfDay()->toDateTimeString();
            $dayEnd = $currentDate->copy()->endOfDay()->toDateTimeString();

            Carbon::setLocale('es');
            $dayLabel = $currentDate->isToday() ? 'Hoy' : $currentDate->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');

            $dailyData = $this->generateReportData($userId, $dayStart, $dayEnd);

            $hasDailyActivity =
                $dailyData['summary']['total_delivered'] > 0 ||
                $dailyData['summary']['total_invoiced'] > 0 ||
                $dailyData['summary']['total_collected'] > 0 ||
                $dailyData['summary']['total_expenses'] > 0 ||
                !empty($dailyData['deliveries']) ||
                !empty($dailyData['previousDayPayments']);

            if ($hasDailyActivity) {
                $hasData = true;
            }

            $generalSummary['total_delivered'] += $dailyData['summary']['total_delivered'];
            $generalSummary['total_invoiced'] += $dailyData['summary']['total_invoiced'];
            $generalSummary['total_collected'] += $dailyData['summary']['total_collected'];
            $generalSummary['total_expenses'] += $dailyData['summary']['total_expenses'];
            $generalSummary['balance'] += $dailyData['summary']['balance'];

            $periodData[] = $dailyData;
            $periodLabels[] = $dayLabel;

            $currentDate->addDay();
        }

        $reportData = [
            'period' => 'personalizado',
            'date' => now()->format('d-m-Yp'),
            'start_date' => $startDateTime->format('d-m-Y'),
            'end_date' => $endDateTime->format('d-m-Y'),
            'period_labels' => $periodLabels,
            'period_data' => $periodData,
            'general_summary' => $generalSummary
        ];

        return $this->pdfService->generateCashRegisterReport($reportData);
    }

    public function generateSimplifiedCashRegisterReport(string $startDate, string $endDate): \Barryvdh\DomPDF\PDF
    {
        if (!$startDate || !$endDate) {
            $startDate = Carbon::today()->format('Y-m-d');
            $endDate = Carbon::today()->format('Y-m-d');
        }

        $userId = AuthHelper::getUserId();

        $startDateTime = $this->parseDate($startDate);
        $endDateTime = $this->parseDate($endDate);

        $startDateTime = $startDateTime->startOfDay();
        $endDateTime = $endDateTime->endOfDay();

        $dailyData = [];
        $totalSummary = [
            'total_delivered' => 0,
            'total_invoiced' => 0,
            'total_collected' => 0,
            'total_expenses' => 0,
            'total_balance' => 0
        ];

        $currentDate = $startDateTime->copy();
        $hasData = false;
        $allDeliveries = [];

        while ($currentDate->lte($endDateTime)) {
            $dayStart = $currentDate->copy()->startOfDay()->toDateTimeString();
            $dayEnd = $currentDate->copy()->endOfDay()->toDateTimeString();

            Carbon::setLocale('es');
            $dayLabel = $currentDate->isToday() ? 'Hoy' : $currentDate->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');

            $stats = $this->cashService->getStatsByPeriod($userId, $dayStart, $dayEnd);

            $expenses = (float)AuthHelper::getActualUser()->companyBills()
                ->whereBetween('date', [$dayStart, $dayEnd])
                ->sum('amount');

            $balance = $stats['total_collected'] - $expenses;

            $dailyDeliveries = $this->cashService->getCashRegisterDeliveries($userId, $dayStart, $dayEnd);

            if ($stats['total_delivered'] > 0 || $stats['total_collected'] > 0 || $expenses > 0) {
                $hasData = true;
            }

            $dailyData[] = [
                'date' => $dayLabel,
                'delivered' => $stats['total_delivered'],
                'invoiced' => $stats['total_invoiced'],
                'collected' => $stats['total_collected'],
                'expenses' => $expenses,
                'balance' => $balance,
                'deliveries' => $dailyDeliveries
            ];

            $allDeliveries = array_merge($allDeliveries, $dailyDeliveries);

            $totalSummary['total_delivered'] += $stats['total_delivered'];
            $totalSummary['total_invoiced'] += $stats['total_invoiced'];
            $totalSummary['total_collected'] += $stats['total_collected'];
            $totalSummary['total_expenses'] += $expenses;
            $totalSummary['total_balance'] += $balance;

            $currentDate->addDay();
        }

        $reportData = [
            'start_date' => $startDateTime->format('Y-m-d'),
            'end_date' => $endDateTime->format('Y-m-d'),
            'daily_data' => $dailyData,
            'summary' => $totalSummary,
            'all_deliveries' => $allDeliveries
        ];

        return $this->pdfService->generateSimplifiedCashRegisterReport($reportData);
    }

    private function generateReportData(int $userId, string $startDate, string $endDate): array
    {
        $stats = $this->cashService->getStatsByPeriod($userId, $startDate, $endDate);

        $totalExpenses = (float)AuthHelper::getActualUser()->companyBills()
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $balance = $stats['total_collected'] - $totalExpenses;
        $deliveries = $this->cashService->getCashRegisterDeliveries($userId, $startDate, $endDate);
        $deliveriesByStatus = $this->cashService->getDeliveriesByStatus($userId, $startDate, $endDate);

        $startDateObj = Carbon::parse($startDate);
        $endDateObj = Carbon::parse($endDate);
        $formattedStartDate = $startDateObj->startOfDay()->toDateTimeString();
        $formattedEndDate = $endDateObj->endOfDay()->toDateTimeString();

        $previousDayPayments = $this->getPreviousDayPayments($userId, $formattedStartDate, $formattedEndDate);

        $courierSummary = $this->cashService->getCourierDeliverySummary($userId, $startDate, $endDate);

        $clientDebtSummary = $this->cashService->getClientDebtSummary($userId, $startDate, $endDate) ?? [];
        $clientPaymentSummary = $this->cashService->getClientPaymentSummary($userId, $startDate, $endDate) ?? [];

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
            'previousDayPayments' => $previousDayPayments,
            'courierSummary' => $courierSummary,
            'clientDebtSummary' => $clientDebtSummary,
            'clientPaymentSummary' => $clientPaymentSummary
        ];
    }

    private function getPreviousDayPayments(int $userId, string $startDate, string $endDate): array
    {
        return Delivery::with(['client', 'anonymousClient', 'debt.payments'])
            ->where('user_id', $userId)
            ->where('date', '<=', $startDate)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->WhereHas('debt.payments', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                });
            })
            ->get()
            ->map(function ($delivery) {
                $paidAmount = $delivery->debt->payments->sum('amount');
                $pendingAmount = max(0, $delivery->amount - $paidAmount);

                $paymentDetails = $delivery->debt->payments
                    ->sortBy(function ($payment) {
                        return $payment->created_at;
                    })
                    ->map(function ($payment) {
                        return [
                            'date' => $payment->date ? $payment->date->format('d/m/Y') : $payment->created_at->format('d/m/Y'),
                            'amount' => (float)$payment->amount,
                            'payment_method' => PaymentMethodTranslator::toSpanish($payment->method ?? 'Efectivo'),
                        ];
                    })->values()->toArray();

                return [
                    'number' => $delivery->number,
                    'date' => $delivery->date->format('d/m/Y'),
                    'client' => $delivery->client_id
                        ? $delivery->client->legal_name
                        : $delivery->anonymousClient->legal_name,
                    'is_anonymous_client' => !$delivery->client_id,
                    'total_amount' => (float)$delivery->amount,
                    'paid_amount' => (float)$paidAmount,
                    'pending_amount' => (float)$pendingAmount,
                    'payment_status' => $delivery->payment_status,
                    'payment_details' => $paymentDetails
                ];
            })->toArray();
    }
}
