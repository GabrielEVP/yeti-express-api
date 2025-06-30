<?php

namespace App\Debt\Services;

use App\Debt\Models\Debt;
use App\Debt\Repositories\IDebtRepository;
use App\Delivery\Models\Delivery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DebtService implements IDebtRepository
{
    public function getAllUnPaidDebtsAmount(): float
    {
        return Auth::user()->debts()
            ->whereNot('status', 'paid')
            ->leftJoin(
                DB::raw('(SELECT debt_id, SUM(amount) as total_paid FROM debt_payments GROUP BY debt_id) as debt_payments_sum'),
                'debts.id',
                '=',
                'debt_payments_sum.debt_id'
            )
            ->selectRaw('SUM(debts.amount - COALESCE(debt_payments_sum.total_paid, 0)) as pending_amount')
            ->value('pending_amount') ?? 0;
    }

    public function getClientsWithDebts(): Collection
    {
        $clients = Auth::user()->clients()
            ->whereHas('debts', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->with([
                'debts' => function ($query) {
                    $query->where('user_id', Auth::id())
                        ->with('payments');
                }
            ])
            ->withCount([
                'debts' => function ($query) {
                    $query->where('user_id', Auth::id())->whereNot('status', 'paid');
                }
            ])
            ->get();

        $clients->each(function ($client) {
            $totalDebt = 0;

            foreach ($client->debts as $debt) {
                $totalPaid = $debt->payments->sum('amount');
                $remainingAmount = $debt->amount - $totalPaid;
                $totalDebt += max(0, $remainingAmount);
            }

            $client->total_debt_amount = $totalDebt;
            unset($client->debts);
        });

        return $clients;
    }

    public function getClientDebtStats(string $clientId): array
    {
        $totalDeliveriesWithDebt = Delivery::where('client_id', $clientId)
            ->where('payment_status', '!=', 'PAID')
            ->whereHas('debt')
            ->distinct('id')
            ->count('id');

        $totalPending = Debt::where('client_id', $clientId)
            ->where('user_id', Auth::id())
            ->whereIn('status', ['pending', 'partial_paid'])
            ->leftJoin(
                DB::raw('(SELECT debt_id, SUM(amount) as total_paid FROM debt_payments GROUP BY debt_id) as debt_payments_sum'),
                'debts.id',
                '=',
                'debt_payments_sum.debt_id'
            )
            ->selectRaw('SUM(debts.amount - COALESCE(debt_payments_sum.total_paid, 0)) as pending_amount')
            ->value('pending_amount') ?? 0;

        return [
            'total_deliveries_with_debt' => $totalDeliveriesWithDebt,
            'total_pending' => (float)$totalPending,
        ];
    }

    public function getDeliveriesWithDebtByClient(string $clientId): Collection
    {
        return Auth::user()->deliveries()->with(['debt', 'debt.payments'])
            ->whereHas('debt', function ($query) use ($clientId) {
                $query->where('user_id', Auth::id())
                    ->where('client_id', $clientId);
            })
            ->where('client_id', $clientId)
            ->get()
            ->map(function ($delivery) {
                $debt = $delivery->debt;

                if ($debt) {
                    $totalPaid = $debt->payments->sum('amount');
                    $remainingAmount = $debt->amount - $totalPaid;
                    $delivery->debt_id = $debt->id;
                    $delivery->debt_remaining_amount = max(0, $remainingAmount);
                    $delivery->debt_status = $remainingAmount > 0 ? 'pending' : 'paid';
                } else {
                    $delivery->debt_status = 'no_debt';
                    $delivery->debt_remaining_amount = 0;
                }

                unset($delivery->debt);
                return $delivery;
            })
            ->sortBy(function ($delivery) {
                return match ($delivery->debt_status) {
                    'pending' => 0,
                    'paid' => 1,
                    default => 2,
                };
            })->values();
    }

    public function filterDeliveriesWithDebtByStatus(string $clientId, ?string $status): Collection
    {
        $query = Delivery::with(['debt', 'debt.payments'])
            ->whereHas('debt', function ($debtQuery) use ($clientId, $status) {
                $debtQuery->where('user_id', Auth::id())
                    ->where('client_id', $clientId);

                if ($status !== null) {
                    $debtQuery->where('status', $status);
                }
            })
            ->where('client_id', $clientId);

        return $query->get()->map(function ($delivery) {
            $debt = $delivery->debt;

            if ($debt) {
                $totalPaid = $debt->payments->sum('amount');
                $remainingAmount = $debt->amount - $totalPaid;
                $delivery->debt_id = $debt->id;
                $delivery->debt_remaining_amount = max(0, $remainingAmount);
            }

            unset($delivery->debt);
            return $delivery;
        });
    }
}

