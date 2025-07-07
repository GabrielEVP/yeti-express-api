<?php

namespace App\Debt\Repositories;

use App\Client\Models\Client;
use Illuminate\Database\Eloquent\Collection;

interface IPDFDebtRepository
{
    public function getUnpaidClientsWithDebts(): Collection;

    public function getClientDebtsWithFilters(Client $client, array $dateRange): Client;

    public function getAllClientsDebtsWithFilters(array $dateRange): Collection;
}

