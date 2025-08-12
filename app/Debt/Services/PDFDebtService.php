<?php

namespace App\Debt\Services;

use App\Client\Models\Client;
use App\Debt\Repositories\IPDFDebtRepository;
use Carbon\Carbon;

class PDFDebtService implements IPDFDebtRepository
{
    private function parseDateRange(array $dateRange): array
    {
        $startDate = !empty($dateRange['startDate'])
            ? Carbon::parse($dateRange['startDate'])->startOfDay()->toDateString()
            : Carbon::now()->startOfDay()->toDateString();

        $endDate = !empty($dateRange['endDate'])
            ? Carbon::parse($dateRange['endDate'])->endOfDay()->toDateString()
            : Carbon::now()->endOfDay()->toDateString();

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }


    public function getUnpaidClientsWithDebts(): \Illuminate\Database\Eloquent\Collection
    {
        $clients = Client::whereHas('debts', function ($query) {
            $query->whereIn('status', ['pending', 'partial_paid']);
        })
            ->with([
                'debts' => function ($query) {
                    $query->whereIn('status', ['pending', 'partial_paid'])
                        ->with(['payments', 'delivery.service', 'delivery.anonymousClient']);
                }
            ])
            ->get();

        return $clients;
    }

    public function getSpecificClientWithUnpaidDebts(Client $client): Client
    {
        $client->load([
            'debts' => function ($query) {
                $query->whereIn('status', ['pending', 'partial_paid'])
                    ->with(['payments', 'delivery.service', 'delivery.anonymousClient']);
            }
        ]);

        return $client;
    }

    public function getClientDebtsWithFilters(Client $client, array $dateRange): Client
    {
        $dateRange = $this->parseDateRange($dateRange);

        $client->load([
            'debts' => function ($query) use ($dateRange) {
                $query->where(function ($subQuery) use ($dateRange) {
                    $subQuery->whereHas('payments', function ($paymentQuery) use ($dateRange) {
                        $paymentQuery->whereDate('date', '>=', $dateRange['startDate'])
                            ->whereDate('date', '<=', $dateRange['endDate']);
                    })
                        ->orWhereHas('delivery', function ($deliveryQuery) use ($dateRange) {
                            $deliveryQuery->whereDate('date', '>=', $dateRange['startDate'])
                                ->whereDate('date', '<=', $dateRange['endDate']);
                        });
                })->with(['payments', 'delivery.service', 'delivery.anonymousClient']);
            }
        ]);
        return $client;
    }

    public function getAllClientsDebtsWithFilters(array $dateRange): \Illuminate\Database\Eloquent\Collection
    {
        $dateRange = $this->parseDateRange($dateRange);

        return Client::whereHas('debts', function ($query) use ($dateRange) {
            $query->whereHas('payments', function ($paymentQuery) use ($dateRange) {
                $paymentQuery->whereDate('date', '>=', $dateRange['startDate'])
                    ->whereDate('date', '<=', $dateRange['endDate']);
            })->orWhereHas('delivery', function ($deliveryQuery) use ($dateRange) {
                $deliveryQuery->whereDate('date', '>=', $dateRange['startDate'])
                    ->whereDate('date', '<=', $dateRange['endDate']);
            });
        })
            ->with([
                'debts' => function ($query) use ($dateRange) {
                    $query->where(function ($subQuery) use ($dateRange) {
                        $subQuery->whereHas('payments', function ($paymentQuery) use ($dateRange) {
                            $paymentQuery->whereDate('date', '>=', $dateRange['startDate'])
                                ->whereDate('date', '<=', $dateRange['endDate']);
                        })->orWhereHas('delivery', function ($deliveryQuery) use ($dateRange) {
                            $deliveryQuery->whereDate('date', '>=', $dateRange['startDate'])
                                ->whereDate('date', '<=', $dateRange['endDate']);
                        });
                    })->with(['payments', 'delivery.service', 'delivery.anonymousClient']);
                }
            ])
            ->get();
    }
}
