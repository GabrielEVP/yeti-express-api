<?php

namespace App\Debt\Controllers;

use App\Core\Controllers\Controller;
use App\Debt\DTO\FilterRequestDeliveriesWithDebtDTO;
use App\Debt\Repositories\IDebtRepository;
use App\Debt\Requests\FilterDebtByStatusRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

