<?php

namespace App\Debt\Repositories;

use App\Debt\DTO\ClientPaymentRequestDTO;
use App\Debt\DTO\DebtPaymentCollectionDTO;
use App\Debt\DTO\DebtPaymentDTO;
use App\Debt\DTO\FullPaymentRequestDTO;
use App\Debt\DTO\PartialPaymentRequestDTO;
use Illuminate\Support\Collection;

interface IDebtPaymentRepository
{
    public function getAll(): DebtPaymentCollectionDTO;

    public function storeFullPayment(FullPaymentRequestDTO $request): DebtPaymentDTO;

    public function storePartialPayment(PartialPaymentRequestDTO $request): DebtPaymentDTO;

    public function payAllDebtsForClient(ClientPaymentRequestDTO $request): DebtPaymentCollectionDTO;

    public function payPartialAmountForClient(ClientPaymentRequestDTO $request): DebtPaymentCollectionDTO;
}
