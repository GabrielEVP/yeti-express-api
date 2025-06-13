<?php

namespace App\Http\Controllers\Api\Debt;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Debt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DebtController extends Controller
{
    public function index(): JsonResponse
    {
        $debts = Debt::with(['client', 'delivery', 'payments'])
            ->where('user_id', Auth::id())
            ->get();

        return response()->json($debts, 200);
    }

    public function clientsWithDebt(): JsonResponse
    {
        $clients = Client::whereHas('debts', function ($query) {
            $query->where('user_id', Auth::id());
        })
            ->withCount([
                'debts' => function ($query) {
                    $query->where('user_id', Auth::id());
                }
            ])
            ->get();

        return response()->json($clients, 200);
    }

    public function show(Debt $debt): JsonResponse
    {
        if ($debt->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $debt->load(['client', 'delivery', 'payments']);
        return response()->json($debt, 200);
    }

    public function destroy(Debt $debt): JsonResponse
    {
        if ($debt->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $debt->delete();
        return response()->json(null, 204);
    }

    public function stats(): JsonResponse
    {
        $userId = Auth::id();

        $debtsQuery = Debt::where('user_id', $userId);

        $totalDeliveriesWithDebt = $debtsQuery->distinct('delivery_id')->count('delivery_id');
        $totalInvoiced = $debtsQuery->sum('amount');
        $totalPaid = (clone $debtsQuery)->where('status', 'paid')->sum('amount');
        $totalPending = (clone $debtsQuery)
            ->whereIn('status', ['pending', 'partial_paid'])
            ->sum('amount');

        return response()->json([
            'total_deliveries_with_debt' => $totalDeliveriesWithDebt,
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'total_pending' => $totalPending,
        ]);
    }

    public function filterByStatus(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = Debt::with(['delivery', 'payments'])
            ->where('user_id', Auth::id());

        if ($status !== null) {
            if (!in_array($status, ['pending', 'partial_paid', 'paid'])) {
                return response()->json(['message' => 'Invalid status filter'], 422);
            }

            $query->where('status', $status);
        }

        $debts = $query->get();

        return response()->json($debts, 200);
    }

}
