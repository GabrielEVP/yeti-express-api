<?php

namespace App\Debt\Services;

use App\Core\DTO\PaginatedDTO;
use App\Debt\DTO\ClientDebtStatsDTO;
use App\Debt\DTO\ClientWithDebtDTO;
use App\Debt\DTO\DeliveryWithDebtDTO;
use App\Debt\DTO\FilterRequestDeliveriesWithDebtDTO;
use App\Debt\DTO\UnpaidDebtsAmountDTO;
use App\Debt\Models\Debt;
use App\Debt\Repositories\IDebtRepository;
use App\Delivery\Models\Delivery;
use App\Shared\Services\AuthHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DebtService implements IDebtRepository
{

    private function baseQuery()
    {
        return Debt::query()
            ->where('user_id', AuthHelper::getUserId());
    }

    public function getAllUnPaidDebtsAmount(): UnpaidDebtsAmountDTO
    {
        $amount = $this->baseQuery()
            ->whereNot('status', 'paid')
            ->leftJoin(
                DB::raw('(SELECT debt_id, SUM(amount) as total_paid FROM debt_payments GROUP BY debt_id) as debt_payments_sum'),
                'debts.id',
                '=',
                'debt_payments_sum.debt_id'
            )
            ->selectRaw('SUM(debts.amount - COALESCE(debt_payments_sum.total_paid, 0)) as pending_amount')
            ->value('pending_amount') ?? 0;

        return new UnpaidDebtsAmountDTO((float)$amount);
    }

    public function getClientsWithDebts(): Collection
    {
        $clients = AuthHelper::getActualUser()->clients()
            ->select('id', 'legal_name', 'registration_number')
            ->whereHas('debts')
            ->get();

        $debtsSummary = DB::table('debts')
            ->select(
                'client_id',
                DB::raw('COUNT(*) as debt_counts'),
                DB::raw('SUM(amount - IFNULL((SELECT SUM(amount) FROM debt_payments WHERE debt_id = debts.id), 0)) as total_pending')
            )
            ->whereIn('client_id', $clients->pluck('id'))
            ->where('status', '!=', 'paid')
            ->groupBy('client_id')
            ->get()
            ->keyBy('client_id');

        return $clients
            ->map(function ($client) use ($debtsSummary) {
                $summary = $debtsSummary[$client->id] ?? null;

                return new ClientWithDebtDTO(
                    id: $client->id,
                    legal_name: $client->legal_name,
                    registration_number: $client->registration_number,
                    debt_counts: (int)($summary->debt_counts ?? 0),
                    total_pending: (float)($summary->total_pending ?? 0)
                );
            })
            ->sortByDesc('total_pending')
            ->values();
    }


    public function getClientDebtStats(string $clientId): ClientDebtStatsDTO
    {
        $totalDeliveriesWithDebt = Delivery::where('client_id', $clientId)
            ->where('payment_status', '!=', 'PAID')
            ->whereHas('debt')
            ->distinct('id')
            ->count('id');

        $totalPending = Debt::where('client_id', $clientId)
            ->where('user_id', AuthHelper::getUserId())
            ->whereIn('status', ['pending', 'partial_paid'])
            ->leftJoin(
                DB::raw('(SELECT debt_id, SUM(amount) as total_paid FROM debt_payments GROUP BY debt_id) as debt_payments_sum'),
                'debts.id',
                '=',
                'debt_payments_sum.debt_id'
            )
            ->selectRaw('SUM(debts.amount - COALESCE(debt_payments_sum.total_paid, 0)) as pending_amount')
            ->value('pending_amount') ?? 0;

        return new ClientDebtStatsDTO(
            total_deliveries_with_debt: $totalDeliveriesWithDebt,
            total_pending: (float)$totalPending
        );
    }

    public function filterDeliveriesWithDebtByStatus(FilterRequestDeliveriesWithDebtDTO $filterDTO): PaginatedDTO
    {
        $query = Delivery::query()
            ->select('deliveries.id', 'deliveries.number', 'deliveries.payment_status', 'deliveries.date', 'deliveries.client_id')
            ->join('debts', 'debts.delivery_id', '=', 'deliveries.id')
            ->leftJoin('debt_payments', 'debts.id', '=', 'debt_payments.debt_id')
            ->where('deliveries.client_id', $filterDTO->client_id)
            ->when($filterDTO->status, function ($query, $status) {
                $query->where('debts.status', $status);
            }, function ($query) {
                $query->where('debts.status', '!=', 'paid');
            })
            ->groupBy('deliveries.id', 'deliveries.number', 'deliveries.payment_status', 'deliveries.date')
            ->selectRaw('
                MAX(debts.id) as debt_id,
                MAX(debts.amount) as amount,
                MAX(debts.amount) - COALESCE(SUM(debt_payments.amount), 0) as debt_remaining_amount
            ');


        $paginator = $query->paginate($filterDTO->perPage, ['*'], 'page', $filterDTO->page);

        $deliveriesWithDebt = $paginator->getCollection()->map(function ($delivery) {
            return DeliveryWithDebtDTO::fromArray($delivery->toArray());
        });

        return new PaginatedDTO(
            $deliveriesWithDebt,
            $paginator->currentPage(),
            $paginator->perPage(),
            $paginator->total()
        );
    }
}
