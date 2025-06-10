<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DebtFullPaymentRequest;
use App\Http\Requests\DebtPartialPaymentRequest;
use App\Models\DebtPayment;
use App\Models\Debt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DebtPaymentController extends Controller
{
    public function index(): JsonResponse
    {
        $payments = DebtPayment::where('user_id', Auth::id())
            ->with('debt')
            ->get();
        return response()->json($payments, 200);
    }

    public function storeFullPayment(Request $request): JsonResponse
    {
        $debt = Debt::findOrFail($request->input('debt_id'));
        $this->authorizeOwner($debt);

        $payment = $debt->payments()->create([
            'date' => now(),
            'amount' => $debt->amount,
            'method' => $request->input('method'),
            'user_id' => Auth::id(),
        ]);

        $debt->updateStatusBasedOnPayments();

        return response()->json($payment, 201);
    }

    public function storePartialPayment(Request $request): JsonResponse
    {
        $debt = Debt::findOrFail($request->input('debt_id'));
        $this->authorizeOwner($debt);

        $payment = $debt->payments()->create([
            'date' => now(),
            'amount' => $request->input('amount'),
            'method' => $request->input('method'),
            'user_id' => Auth::id(),
        ]);

        $debt->updateStatusBasedOnPayments();

        return response()->json($payment, 201);
    }

    public function show(DebtPayment $debtPayment): JsonResponse
    {
        $this->authorizeOwner($debtPayment->debt);
        return response()->json($debtPayment->load('debt'), 200);
    }

    private function authorizeOwner(Debt $debt): void
    {
        abort_if($debt->user_id !== Auth::id(), 403, 'No tienes permiso para acceder a esta deuda.');
    }
}
