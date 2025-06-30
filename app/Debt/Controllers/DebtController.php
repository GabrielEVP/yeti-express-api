<?php

namespace App\Debt\Controllers;

use App\Debt\Repositories\IDebtRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    public function __construct(private readonly IDebtRepository $debtRepo)
    {
    }

    public function getAllUnPaidDebtsAmount(): JsonResponse
    {
        return response()->json(['total_amount' => $this->debtRepo->getAllUnPaidDebtsAmount()]);
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

    public function filterDeliveryWithDebtByStatusByClient(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $clientId = $request->route('client');

        if (!$clientId) {
            return response()->json(['message' => 'Client ID is required'], 422);
        }

        if ($status !== null && !in_array($status, ['pending', 'partial_paid', 'paid'])) {
            return response()->json(['message' => 'Invalid status filter'], 422);
        }

        return response()->json($this->debtRepo->filterDeliveriesWithDebtByStatus($clientId, $status));
    }
}
