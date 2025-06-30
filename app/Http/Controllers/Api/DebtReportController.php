<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Client\Models\Client;
use App\Services\PDFService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DebtReportController extends Controller
{
    protected PDFService $pdfService;

    public function __construct(PDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    public function unpaidDebtsReport(): \Illuminate\Http\Response
    {
        $this->authorizeOwner(new Client());

        $clients = Client::whereHas('debts', function ($query) {
            $query->whereIn('status', ['pending', 'partial_paid']);
        })
            ->with([
                'debts' => function ($query) {
                    $query->whereIn('status', ['pending', 'partial_paid'])
                        ->with(['payments', 'delivery.service']);
                }
            ])
            ->get();

        $pdf = $this->pdfService->generateUnpaidDebtsReport($clients);
        return $pdf->stream("unpaid-debts-report.pdf");
    }

    public function clientDebtReport(Client $client, Request $request): \Illuminate\Http\Response
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$startDate || !$endDate) {
            $startDate = \Carbon\Carbon::today()->format('Y-m-d');
            $endDate = \Carbon\Carbon::today()->format('Y-m-d');
        }

        $this->authorizeOwner($client);

        $client->load([
            'debts' => function ($query) use ($startDate, $endDate) {
                $query->where(function ($subQuery) use ($startDate, $endDate) {
                    $subQuery->whereHas('payments', function ($paymentQuery) use ($startDate, $endDate) {
                        $paymentQuery->whereDate('date', '>=', $startDate)
                            ->whereDate('date', '<=', $endDate);
                    })
                        ->orWhereHas('delivery', function ($deliveryQuery) use ($startDate, $endDate) {
                            $deliveryQuery->whereDate('date', '>=', $startDate)
                                ->whereDate('date', '<=', $endDate);
                        });
                })->with(['payments', 'delivery.service']);
            }
        ]);
        $pdf = $this->pdfService->generateClientDebtReport($client, $startDate, $endDate);
        return $pdf->stream("client-debt-report-{$client->id}.pdf");
    }

    public function allClientsDebtReport(Request $request): \Illuminate\Http\Response
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (!$startDate || !$endDate) {
            $startDate = \Carbon\Carbon::today()->format('Y-m-d');
            $endDate = \Carbon\Carbon::today()->format('Y-m-d');
        }

        $clients = Client::whereHas('debts', function ($query) use ($startDate, $endDate) {
            $query->whereHas('payments', function ($paymentQuery) use ($startDate, $endDate) {
                $paymentQuery->whereDate('date', '>=', $startDate)
                    ->whereDate('date', '<=', $endDate);
            })
                ->orWhereHas('delivery', function ($deliveryQuery) use ($startDate, $endDate) {
                    $deliveryQuery->whereDate('date', '>=', $startDate)
                        ->whereDate('date', '<=', $endDate);
                });
        })
            ->with([
                'debts' => function ($query) use ($startDate, $endDate) {
                    $query->where(function ($subQuery) use ($startDate, $endDate) {
                        $subQuery->whereHas('payments', function ($paymentQuery) use ($startDate, $endDate) {
                            $paymentQuery->whereDate('date', '>=', $startDate)
                                ->whereDate('date', '<=', $endDate);
                        })
                            ->orWhereHas('delivery', function ($deliveryQuery) use ($startDate, $endDate) {
                                $deliveryQuery->whereDate('date', '>=', $startDate)
                                    ->whereDate('date', '<=', $endDate);
                            });
                    })->with(['payments', 'delivery.service']);
                }
            ])
            ->get();


        $pdf = $this->pdfService->generateAllClientsDebtReport($clients, $startDate, $endDate);
        return $pdf->stream("all-clients-debt-report.pdf");
    }

    private function authorizeOwner($model): void
    {
        abort_if(!Auth::user(), 403, 'No tienes permiso para acceder a este recurso.');
    }
}
