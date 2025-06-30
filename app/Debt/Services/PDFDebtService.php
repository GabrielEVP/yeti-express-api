<?php

namespace App\Debt\Services;

use App\Client\Models\Client;
use App\Debt\Repositories\IPDFDebtRepository;
use Illuminate\Support\Collection;

class PDFDebtService implements IPDFDebtRepository
{
    public function getUnpaidClientsWithDebts(): Collection
    {
        return Client::whereHas('debts', function ($query) {
            $query->whereIn('status', ['pending', 'partial_paid']);
        })
            ->with([
                'debts' => function ($query) {
                    $query->whereIn('status', ['pending', 'partial_paid'])
                        ->with(['payments', 'delivery.service']);
                }
            ])
            ->get();
    }

    public function getClientDebtsWithFilters(Client $client, string $startDate, string $endDate): Client
    {
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

        return $client;
    }

    public function getAllClientsDebtsWithFilters(string $startDate, string $endDate): Collection
    {
        return Client::whereHas('debts', function ($query) use ($startDate, $endDate) {
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
    }
}
