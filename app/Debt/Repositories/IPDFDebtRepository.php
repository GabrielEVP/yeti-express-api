<?php

namespace App\Debt\Repositories;

use App\Client\Models\Client;
use App\Debt\DTO\ClientDebtsDTO;
use App\Debt\DTO\ClientsDebtsCollectionDTO;
use App\Debt\DTO\DateRangeDTO;

interface IPDFDebtRepository
{
    public function getUnpaidClientsWithDebts(): ClientsDebtsCollectionDTO;

    public function getClientDebtsWithFilters(Client $client, DateRangeDTO $dateRange): ClientDebtsDTO;

    public function getAllClientsDebtsWithFilters(DateRangeDTO $dateRange): ClientsDebtsCollectionDTO;
}

