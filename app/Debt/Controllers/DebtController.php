<?php

namespace App\Debt\Controllers;

use App\Core\Controllers\Controller;
use App\Debt\Repositories\IDebtRepository;
use App\Debt\Requests\FilterDebtByStatusRequest;
use Illuminate\Http\JsonResponse;

class DebtController extends Controller
{
    public function __construct(private readonly IDebtRepository $debtRepo)
    {
    }

    public function getAllUnPaidDebtsAmount(): JsonResponse
    {
        $unpaidAmount = $this->debtRepo->getAllUnPaidDebtsAmount();
        return response()->json($unpaidAmount);
    }

    public function clientsWithDebt(): JsonResponse
    {
        return response()->json($this->debtRepo->getClientsWithDebts());
    }

    public function stats(string $clientId): JsonResponse
    {
        return response()->json($this->debtRepo->getClientDebtStats($clientId));
    }

    public function loadDeliveryWithDebtByClient(string $clientId): JsonResponse
    {
        return response()->json($this->debtRepo->getDeliveriesWithDebtByClient($clientId));
    }

    public function filterDeliveryWithDebtByStatusByClient(FilterDebtByStatusRequest $request): JsonResponse
    {
        $filterDTO = $request->toDTO();

        return response()->json(
            $this->debtRepo->filterDeliveriesWithDebtByStatus(
                $filterDTO->clientId,
                $filterDTO->status,
                $filterDTO->page,
                $filterDTO->perPage
            )->toArray()
        );
    }
}

