<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Debt;
use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class DebtController extends Controller
{
    public function clientsWithDebt(): JsonResponse
    {
        $clients = Auth::user()->clients()->whereHas('debts', function ($query) {
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
    public function stats(): JsonResponse
    {
        $userId = Auth::id();

        $totalDeliveriesWithDebt = Debt::where('user_id', $userId)
            ->distinct('delivery_id')
            ->count('delivery_id');

        $totalInvoiced = Debt::where('debts.user_id', $userId)
            ->join('deliveries', 'debts.delivery_id', '=', 'deliveries.id')
            ->sum('deliveries.amount');

        $totalPaid = DB::table('debt_payments')
            ->join('debts', 'debt_payments.debt_id', '=', 'debts.id')
            ->where('debts.user_id', $userId)
            ->sum('debt_payments.amount');

        $totalPending = Debt::where('debts.user_id', $userId)
            ->whereIn('debts.status', ['pending', 'partial_paid'])
            ->leftJoin(
                DB::raw('(SELECT debt_id, SUM(amount) as total_paid FROM debt_payments GROUP BY debt_id) as debt_payments_sum'),
                'debts.id',
                '=',
                'debt_payments_sum.debt_id'
            )
            ->selectRaw('SUM(debts.amount - COALESCE(debt_payments_sum.total_paid, 0)) as pending_amount')
            ->value('pending_amount') ?? 0;

        return response()->json([
            'total_deliveries_with_debt' => $totalDeliveriesWithDebt,
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'total_pending' => $totalPending,
        ]);
    }

    public function loadDeliveryWithDebtByClient(string $client_id): JsonResponse
    {
        $deliveries = Auth::user()->deliveries()->with(['debt', 'debt.payments'])
            ->whereHas('debt', function ($query) use ($client_id) {
                $query->where('user_id', Auth::id())
                    ->where('client_id', $client_id);
            })
            ->where('client_id', $client_id)
            ->get();

        $deliveries = $deliveries->map(function ($delivery) {
            $debt = $delivery->debt;

            if ($debt) {
                $totalPaid = $debt->payments->sum('amount');
                $remainingAmount = $debt->amount - $totalPaid;
                $delivery->debt_id = $debt->id;
                $delivery->debt_remaining_amount = max(0, $remainingAmount);

                // Puedes usar esto como indicador de estado
                $delivery->debt_status = $remainingAmount > 0 ? 'pending' : 'paid';
            } else {
                $delivery->debt_status = 'no_debt';
                $delivery->debt_remaining_amount = 0;
            }

            unset($delivery->debt);
            return $delivery;
        });

        // Ordenamos: primero por estado (pendiente > pagado > sin deuda), luego por fecha (si lo deseas)
        $deliveries = $deliveries->sortBy(function ($delivery) {
            return match ($delivery->debt_status) {
                'pending' => 0,
                'paid' => 1,
                'no_debt' => 2,
            };
        })->values(); // `values()` para resetear los índices del array

        return response()->json($deliveries, 200);
    }

    public function filterDeliveryWithDebtByStatusByClient(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $client_id = $request->query('client_id');

        if (!$client_id) {
            return response()->json(['message' => 'Client ID is required'], 422);
        }

        if ($status !== null && !in_array($status, ['pending', 'partial_paid', 'paid'])) {
            return response()->json(['message' => 'Invalid status filter'], 422);
        }

        $query = Delivery::with(['debt', 'debt.payments'])
            ->whereHas('debt', function ($debtQuery) use ($client_id, $status) {
                $debtQuery->where('user_id', Auth::id())
                    ->where('client_id', $client_id);

                if ($status !== null) {
                    $debtQuery->where('status', $status);
                }
            })
            ->where('client_id', $client_id);

        $deliveries = $query->get();

        $deliveries = $deliveries->map(function ($delivery) {
            $debt = $delivery->debt;

            if ($debt) {
                $totalPaid = $debt->payments->sum('amount');
                $remainingAmount = $debt->amount - $totalPaid;
                $delivery->debt_id = $debt->id;
                $delivery->debt_remaining_amount = max(0, $remainingAmount);
            }

            unset($delivery->debt);

            return $delivery;
        });

        return response()->json($deliveries, 200);
    }

}
