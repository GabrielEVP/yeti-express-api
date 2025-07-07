<?php

namespace App\Debt\Controllers;

use App\Core\Controllers\Controller;
use App\Debt\DTO\FilterRequestDeliveriesWithDebtDTO;
use App\Debt\Repositories\IDebtRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    public function __construct(private readonly IDebtRepository $debtRepo)
    {
    }

    public function getAllDebtsAmount(): JsonResponse
    {
        $unpaidAmount = $this->debtRepo->getAllUnPaidDebtsAmount();
        return response()->json($unpaidAmount);
    }

    public function getClientsWithDebt(): JsonResponse
    {
        return response()->json($this->debtRepo->getClientsWithDebts());
    }

    public function getClientStats(string $clientId): JsonResponse
    {
        return response()->json($this->debtRepo->getClientDebtStats($clientId));
    }

    public function filterDeliveryWithDebtByStatusByClient(Request $request): JsonResponse
    {
        $filterDTO = new FilterRequestDeliveriesWithDebtDTO(
            $request->string('search', '')->toString(),
            $request->input('sortBy', 'number'),
            $request->input('sortDirection', 'asc'),
            $request->input('client_id'),
            ($request->input('status') === 'all') ? null : $request->input('status'),
            $request->integer('page', 1),
            $request->integer('per_page', 15)
        );


        $deliveries = $this->debtRepo->filterDeliveriesWithDebtByStatus($filterDTO);

        return response()->json($deliveries);
    }
}

