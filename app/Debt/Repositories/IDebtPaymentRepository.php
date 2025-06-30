<?php

namespace App\Debt\Repositories;

use App\Debt\Models\DebtPayment;
use Illuminate\Support\Collection;

interface IDebtPaymentRepository
{
    public function getAll(): Collection;

    public function storeFullPayment(int $debtId, string $method): DebtPayment;

    public function storePartialPayment(int $debtId, float $amount, string $method): DebtPayment;

    public function payAllDebtsForClient(int $clientId, string $method): array;

    public function payPartialAmountForClient(int $clientId, float $amount, string $method): array;
}
