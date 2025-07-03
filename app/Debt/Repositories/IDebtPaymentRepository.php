<?php

namespace App\Debt\Repositories;

use App\Debt\DTO\DebtPaymentDTO;
use App\Debt\DTO\FormRequestPayAllDTO;
use App\Debt\DTO\FormRequestPayPartialDTO;
use Illuminate\Database\Eloquent\Collection;

interface IDebtPaymentRepository
{
    public function getAll(): Collection;

    public function storeFullPayment(FormRequestPayAllDTO $request): DebtPaymentDTO;

    public function storePartialPayment(FormRequestPayPartialDTO $request): DebtPaymentDTO;

    public function payAllDebtsForClient(FormRequestPayAllDTO $request): void;

    public function payPartialAmountForClient(FormRequestPayPartialDTO $request): void;
}
