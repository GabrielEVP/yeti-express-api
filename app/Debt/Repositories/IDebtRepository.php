<?php

namespace App\Debt\Repositories;

use App\Core\DTO\PaginatedDTO;
use App\Debt\DTO\ClientDebtStatsDTO;
use App\Debt\DTO\FilterRequestDeliveriesWithDebtDTO;
use App\Debt\DTO\UnpaidDebtsAmountDTO;
use Illuminate\Support\Collection;

interface IDebtRepository
{
    public function getAllUnPaidDebtsAmount(): UnpaidDebtsAmountDTO;

    public function getClientsWithDebts(): Collection;

    public function getClientDebtStats(string $clientId): ClientDebtStatsDTO;

    public function filterDeliveriesWithDebtByStatus(FilterRequestDeliveriesWithDebtDTO $filterDTO): PaginatedDTO;
}
