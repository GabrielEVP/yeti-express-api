<?php

namespace App\Debt\Repositories;

use App\Client\Models\Client;
use Illuminate\Support\Collection;

interface IPDFDebtRepository
{
    public function getUnpaidClientsWithDebts(): Collection;

    public function getClientDebtsWithFilters(Client $client, string $startDate, string $endDate): Client;

    public function getAllClientsDebtsWithFilters(string $startDate, string $endDate): Collection;
}
