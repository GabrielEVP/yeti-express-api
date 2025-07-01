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
                $query->where('status', '!=', 'paid');
            })
            ->get();

        $debtsSummary = DB::table('debts')
            ->select('client_id', DB::raw('SUM(amount - IF NULL((SELECT SUM(amount) FROM debt_payments WHERE debt_id = debts.id), 0)) as total_pending'))
            ->whereIn('client_id', $clients->pluck('id'))
            ->where('status', '!=', 'paid')
            ->groupBy('client_id')
            ->get()
            ->keyBy('client_id');

        $clients->each(function ($client) use ($debtsSummary) {
            $client->total_pending = $debtsSummary[$client->id]->total_pending ?? 0;
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
        $userId = Auth::id();

        return Auth::user()->deliveries()
            ->select('deliveries.*')
            ->leftJoin('debts', function ($join) use ($userId) {
                $join->on('debts.delivery_id', '=', 'deliveries.id')
                    ->where('debts.user_id', '=', $userId);
            })
            ->leftJoin('debt_payments', 'debts.id', '=', 'debt_payments.debt_id')
            ->where('deliveries.client_id', $clientId)
            ->groupBy('deliveries.id')
            ->selectRaw('
                MAX(debts.id) as debt_id,
                COALESCE(SUM(debts.amount), 0) as debt_amount,
                COALESCE(SUM(debt_payments.amount), 0) as total_paid,
                CASE
                    WHEN COALESCE(SUM(debts.amount), 0) = 0 THEN "no_debt"
                    WHEN COALESCE(SUM(debts.amount), 0) > COALESCE(SUM(debt_payments.amount), 0) THEN "pending"
                    ELSE "paid"
                END as debt_status,
                GREATEST(COALESCE(SUM(debts.amount), 0) - COALESCE(SUM(debt_payments.amount), 0), 0) as debt_remaining_amount
            ')
            ->get();
    }

    public function filterDeliveriesWithDebtByStatus(string $clientId, ?string $status): Collection
    {
        $userId = Auth::id();

        $query = Delivery::query()
            ->select('deliveries.*')
            ->leftJoin('debts', function ($join) use ($userId, $clientId, $status) {
                $join->on('debts.delivery_id', '=', 'deliveries.id')
                    ->where('debts.user_id', $userId)
                    ->where('debts.client_id', $clientId);

                if ($status !== null) {
                    $join->where('debts.status', $status);
                }
            })
            ->leftJoin('debt_payments', 'debts.id', '=', 'debt_payments.debt_id')
            ->where('deliveries.client_id', $clientId)
            ->groupBy('deliveries.id')
            ->selectRaw('
                MAX(debts.id) as debt_id,
                COALESCE(SUM(debts.amount), 0) as debt_amount,
                COALESCE(SUM(debt_payments.amount), 0) as total_paid,
                GREATEST(COALESCE(SUM(debts.amount), 0) - COALESCE(SUM(debt_payments.amount), 0), 0) as debt_remaining_amount
            ');

        return $query->get();
    }
}

