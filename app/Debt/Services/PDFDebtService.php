<?php

namespace App\Debt\Services;

use App\Client\Models\Client;
use App\Debt\DTO\ClientDebtsDTO;
use App\Debt\DTO\ClientsDebtsCollectionDTO;
use App\Debt\DTO\DateRangeDTO;
use App\Debt\Repositories\IPDFDebtRepository;

class PDFDebtService implements IPDFDebtRepository
{
    public function getUnpaidClientsWithDebts(): ClientsDebtsCollectionDTO
    {
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

        return ClientsDebtsCollectionDTO::fromCollection($clients);
    }

    public function getClientDebtsWithFilters(Client $client, DateRangeDTO $dateRange): ClientDebtsDTO
    {
        $client->load([
            'debts' => function ($query) use ($dateRange) {
                $query->where(function ($subQuery) use ($dateRange) {
                    $subQuery->whereHas('payments', function ($paymentQuery) use ($dateRange) {
                        $paymentQuery->whereDate('date', '>=', $dateRange->startDate)
                            ->whereDate('date', '<=', $dateRange->endDate);
                    })
                        ->orWhereHas('delivery', function ($deliveryQuery) use ($dateRange) {
                            $deliveryQuery->whereDate('date', '>=', $dateRange->startDate)
                                ->whereDate('date', '<=', $dateRange->endDate);
                        });
                })->with(['payments', 'delivery.service']);
            }
        ]);

        return ClientDebtsDTO::fromModel($client);
    }

    public function getAllClientsDebtsWithFilters(DateRangeDTO $dateRange): ClientsDebtsCollectionDTO
    {
        $clients = Client::whereHas('debts', function ($query) use ($dateRange) {
            $query->whereHas('payments', function ($paymentQuery) use ($dateRange) {
                $paymentQuery->whereDate('date', '>=', $dateRange->startDate)
                    ->whereDate('date', '<=', $dateRange->endDate);
            })
                ->orWhereHas('delivery', function ($deliveryQuery) use ($dateRange) {
                    $deliveryQuery->whereDate('date', '>=', $dateRange->startDate)
                        ->whereDate('date', '<=', $dateRange->endDate);
                });
        })
            ->with([
                'debts' => function ($query) use ($dateRange) {
                    $query->where(function ($subQuery) use ($dateRange) {
                        $subQuery->whereHas('payments', function ($paymentQuery) use ($dateRange) {
                            $paymentQuery->whereDate('date', '>=', $dateRange->startDate)
                                ->whereDate('date', '<=', $dateRange->endDate);
                        })
                            ->orWhereHas('delivery', function ($deliveryQuery) use ($dateRange) {
                                $deliveryQuery->whereDate('date', '>=', $dateRange->startDate)
                                    ->whereDate('date', '<=', $dateRange->endDate);
                            });
                    })->with(['payments', 'delivery.service']);
                }
            ])
            ->get();

        return ClientsDebtsCollectionDTO::fromCollection($clients);
    }
}
