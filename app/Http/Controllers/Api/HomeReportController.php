<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Services\DashboardService;
use App\Services\PDFService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeReportController extends Controller
{
    protected PDFService $pdfService;
    protected DashboardService $dashboardService;

    private function parseDate(string $date): \Carbon\Carbon
    {
        $date = trim($date);
        Carbon::setLocale('es');
        return Carbon::parse($date)->startOfDay();
    }

    public function __construct(PDFService $pdfService, DashboardService $dashboardService)
    {
        $this->pdfService = $pdfService;
        $this->dashboardService = $dashboardService;
    }

    public function cashRegisterReport(Request $request): \Illuminate\Http\Response
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$startDate || !$endDate) {
            abort(400, 'Se requieren fechas de inicio y fin para generar el reporte.');
        }

        $userId = auth()->id();

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
            $dayLabel = $currentDate->format('d/m/Y');

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
            $periodLabels[] = "Caja del día: " . $dayLabel;

            $currentDate->addDay();
        }

        if (!$hasData && $generalSummary['total_delivered'] == 0 && $generalSummary['total_collected'] == 0) {
            return response()->view('pdfs.empty-report', [
                'message' => "No hay datos de caja disponibles para el período {$startDateTime->format('Y-m-d')} - {$endDateTime->format('Y-m-d')}",
                'start_date' => $startDateTime->format('Y-m-d'),
                'end_date' => $endDateTime->format('Y-m-d')
            ]);
        }

        $reportData = [
            'period' => 'custom',
            'date' => now()->format('Y-m-d'),
            'start_date' => $startDateTime->format('Y-m-d'),
            'end_date' => $endDateTime->format('Y-m-d'),
            'period_labels' => $periodLabels,
            'period_data' => $periodData,
            'general_summary' => $generalSummary
        ];

        $pdf = $this->pdfService->generateCashRegisterReport($reportData);
        return $pdf->stream("reporte-caja-detallado.pdf");
    }

    public function simplifiedCashRegisterReport(Request $request): \Illuminate\Http\Response
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$startDate || !$endDate) {
            abort(400, 'Se requieren fechas de inicio y fin para generar el reporte.');
        }

        $userId = auth()->id();

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
        $hasData = false; // Bandera para verificar si hay datos en el período
        $allDeliveries = [];

        while ($currentDate->lte($endDateTime)) {
            $dayStart = $currentDate->copy()->startOfDay()->toDateTimeString();
            $dayEnd = $currentDate->copy()->endOfDay()->toDateTimeString();
            $dayLabel = $currentDate->format('Y-m-d');

            $stats = $this->dashboardService->getStatsByPeriod($userId, $dayStart, $dayEnd);

            $expenses = (float) auth()->user()->companyBills()
                ->whereBetween('date', [$dayStart, $dayEnd])
                ->sum('amount');

            $balance = $stats['total_collected'] - $expenses;

            // Obtener los deliveries del día para mostrar el detalle de pagos
            $dailyDeliveries = $this->dashboardService->getCashRegisterDeliveries($userId, $dayStart, $dayEnd);

            // Verificar si hay actividad en este día
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

            // Agregar los deliveries a la lista completa
            $allDeliveries = array_merge($allDeliveries, $dailyDeliveries);

            $totalSummary['total_delivered'] += $stats['total_delivered'];
            $totalSummary['total_invoiced'] += $stats['total_invoiced'];
            $totalSummary['total_collected'] += $stats['total_collected'];
            $totalSummary['total_expenses'] += $expenses;
            $totalSummary['total_balance'] += $balance;

            $currentDate->addDay();
        }

        if (!$hasData && $totalSummary['total_delivered'] == 0 && $totalSummary['total_collected'] == 0) {
            return response()->view('pdfs.empty-report', [
                'message' => "No hay datos de caja disponibles para el período {$startDateTime->format('Y-m-d')} - {$endDateTime->format('Y-m-d')}",
                'start_date' => $startDateTime->format('Y-m-d'),
                'end_date' => $endDateTime->format('Y-m-d')
            ]);
        }

        $reportData = [
            'start_date' => $startDateTime->format('Y-m-d'),
            'end_date' => $endDateTime->format('Y-m-d'),
            'daily_data' => $dailyData,
            'summary' => $totalSummary,
            'all_deliveries' => $allDeliveries
        ];

        $pdf = $this->pdfService->generateSimplifiedCashRegisterReport($reportData);
        return $pdf->stream("reporte-cajas-simplificado.pdf");
    }

    private function generateReportData(int $userId, string $startDate, string $endDate): array
    {
        $stats = $this->dashboardService->getStatsByPeriod($userId, $startDate, $endDate);

        $totalExpenses = (float) auth()->user()->companyBills()
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $balance = $stats['total_collected'] - $totalExpenses;
        $deliveries = $this->dashboardService->getCashRegisterDeliveries($userId, $startDate, $endDate);
        $deliveriesByStatus = $this->dashboardService->getDeliveriesByStatus($userId, $startDate, $endDate);

        // Asegurar que las fechas estén en el formato correcto para las consultas
        $startDateObj = Carbon::parse($startDate);
        $endDateObj = Carbon::parse($endDate);
        $formattedStartDate = $startDateObj->startOfDay()->toDateTimeString();
        $formattedEndDate = $endDateObj->endOfDay()->toDateTimeString();

        // La fecha desde la que queremos buscar pagos (5 años antes)
        $previousDateStart = $startDateObj->copy()->startOfDay()->subYears(5)->toDateTimeString();

        $paidDeliveries = \App\Models\Delivery::with(['client', 'courier', 'service'])
            ->where('user_id', $userId)
            ->where('payment_status', 'paid')
            ->where('payment_type', 'full')
            ->where('date', '<', $formattedStartDate)
            ->where(function ($query) use ($formattedStartDate, $formattedEndDate) {
                $query->whereBetween('updated_at', [$formattedStartDate, $formattedEndDate]);
            })
            ->get()
            ->map(function ($delivery) {
                return [
                    'number' => $delivery->number,
                    'date' => $delivery->date->format('Y-m-d'),
                    'client' => $delivery->client ? $delivery->client->legal_name : 'Sin cliente',
                    'courier' => $delivery->courier ? $delivery->courier->first_name . ' ' . $delivery->courier->last_name : 'Sin repartidor',
                    'service' => $delivery->service->name ?? 'N/A',
                    'amount' => (float) $delivery->amount,
                    'delivery_amount' => (float) $delivery->amount,
                    'payment_date' => $delivery->updated_at->format('Y-m-d'),
                    'payment_type' => 'full'
                ];
            })
            ->toArray();

        // Primero obtenemos todas las deudas con pagos en el período
        $debtsWithPayments = \App\Models\Debt::join('debt_payments', 'debts.id', '=', 'debt_payments.debt_id')
            ->join('deliveries', 'debts.delivery_id', '=', 'deliveries.id')
            ->where('deliveries.user_id', $userId)
            ->where('deliveries.date', '<', $formattedStartDate)
            ->where(function ($query) use ($formattedStartDate, $formattedEndDate) {
                $query->whereBetween('debt_payments.created_at', [$formattedStartDate, $formattedEndDate])
                    ->orWhereBetween('debt_payments.date', [$formattedStartDate, $formattedEndDate]);
            })
            ->select('debts.*', 'deliveries.id as delivery_id')
            ->distinct('debts.id') // Obtenemos deudas únicas
            ->with(['delivery.client', 'delivery.courier', 'delivery.service', 'payments'])
            ->get();

        // Ahora procesamos cada deuda para obtener todos sus pagos
        $partialPayments = collect();
        foreach ($debtsWithPayments as $debt) {
            // Filtramos pagos del período
            $paymentsInPeriod = $debt->payments->filter(function ($payment) use ($formattedStartDate, $formattedEndDate) {
                $paymentDate = $payment->date ? $payment->date : $payment->created_at;
                return $paymentDate->between($formattedStartDate, $formattedEndDate);
            });

            if ($paymentsInPeriod->isNotEmpty()) {
                // Usamos el primer pago del período como representante
                $payment = $paymentsInPeriod->first();
                $partialPayments->push($payment);
            }
        }

        $partialPayments = $partialPayments->map(function ($payment) {
            $delivery = $payment->debt->delivery;
            $totalPaid = $delivery->debt->payments->sum('amount');
            $pendingAmount = $delivery->amount - $totalPaid;

            // Obtener TODOS los pagos para este delivery de forma ordenada por fecha
            $allPaymentsForDelivery = $delivery->debt->payments->sortByDesc(function ($paymentDetail) {
                return $paymentDetail->date ? $paymentDetail->date->timestamp : $paymentDetail->created_at->timestamp;
            });

            // Crear detalles de pago completos para cada pago individual
            $paymentDetails = $allPaymentsForDelivery->map(function ($paymentDetail) {
                return [
                    'date' => $paymentDetail->date ? $paymentDetail->date->format('Y-m-d') : $paymentDetail->created_at->format('Y-m-d'),
                    'amount' => (float) $paymentDetail->amount,
                    'payment_method' => $paymentDetail->method ?? 'Efectivo',
                    'notes' => $paymentDetail->notes ?? ''
                ];
            })->toArray();

            return [
                'id' => $delivery->id, // ID correcto para identificar entregas únicas
                'number' => $delivery->number,
                'date' => $delivery->date->format('Y-m-d'),
                'client' => $delivery->client ? $delivery->client->legal_name : 'Sin cliente',
                'courier' => $delivery->courier ? $delivery->courier->first_name . ' ' . $delivery->courier->last_name : 'Sin repartidor',
                'service' => $delivery->service->name ?? 'N/A',
                'amount' => (float) $payment->amount, // Monto de este pago específico
                'delivery_amount' => (float) $delivery->amount, // Monto total de la entrega
                'total_amount' => (float) $delivery->amount, // Para compatibilidad
                'paid_amount' => (float) $totalPaid, // Monto total pagado hasta ahora
                'pending_amount' => (float) $pendingAmount, // Monto pendiente
                'payment_date' => $payment->created_at->format('Y-m-d'),
                'payment_type' => 'partial',
                'payment_details' => $paymentDetails // Incluimos TODOS los pagos ordenados
            ];
        })
            ->toArray();

        $partialPaymentsByDelivery = [];
        foreach ($partialPayments as $payment) {
            $key = $payment['id'];
            if (!isset($partialPaymentsByDelivery[$key])) {
                $partialPaymentsByDelivery[$key] = $payment;
            } else {
                if (count($payment['payment_details']) > count($partialPaymentsByDelivery[$key]['payment_details'])) {
                    $partialPaymentsByDelivery[$key] = $payment;
                }
            }
        }

        $previousDayPayments = array_merge($paidDeliveries, array_values($partialPaymentsByDelivery));
        $courierSummary = $this->dashboardService->getCourierDeliverySummary($userId, $startDate, $endDate);

        $clientDebtSummary = $this->dashboardService->getClientDebtSummary($userId, $startDate, $endDate) ?? [];
        $clientPaymentSummary = $this->dashboardService->getClientPaymentSummary($userId, $startDate, $endDate) ?? [];

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
}

