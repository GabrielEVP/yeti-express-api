<?php

namespace App\Debt\Repositories;

use Illuminate\Support\Collection;

interface IDebtRepository
{
    public function getAllUnPaidDebtsAmount(): float;

    public function getClientsWithDebts(): Collection;

    public function getClientDebtStats(string $clientId): array;

    public function getDeliveriesWithDebtByClient(string $clientId): Collection;

    public function filterDeliveriesWithDebtByStatus(string $clientId, ?string $status): Collection;
}
