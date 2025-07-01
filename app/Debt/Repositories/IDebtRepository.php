<?php

namespace App\Debt\Repositories;

use App\Debt\DTO\ClientDebtStatsDTO;
use App\Debt\DTO\DebtDeliveryPaginatedDTO;
use App\Debt\DTO\UnpaidDebtsAmountDTO;
use Illuminate\Support\Collection;

interface IDebtRepository
{
    public function getAllUnPaidDebtsAmount(): UnpaidDebtsAmountDTO;

    public function getClientsWithDebts(): Collection;

    public function getClientDebtStats(string $clientId): ClientDebtStatsDTO;

    public function getDeliveriesWithDebtByClient(string $clientId): DebtDeliveryPaginatedDTO;

    public function filterDeliveriesWithDebtByStatus(string $clientId, ?string $status, int $page = 1, int $perPage = 15): DebtDeliveryPaginatedDTO;
}
