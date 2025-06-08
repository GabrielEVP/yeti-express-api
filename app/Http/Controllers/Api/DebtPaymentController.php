<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DebtFullPaymentRequest;
use App\Http\Requests\DebtPartialPaymentRequest;
use App\Models\DebtPayment;
use App\Models\Debt;
use Illuminate\Http\JsonResponse;

class DebtPaymentController extends Controller
{
    public function index(): JsonResponse
    {
        $payments = DebtPayment::with('debt')->get();
        return response()->json($payments, 200);
    }

    public function storeFullPayment(DebtFullPaymentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['date'] = now();

        $debt = Debt::findOrFail($data['debt_id']);
        $data['amount'] = $debt->amount;

        $payment = DebtPayment::create($data);

        $payment->clientDeliveryDebt->updateStatusBasedOnPayments();

        return response()->json($payment->load('debts'), 200);
    }

    public function storePartialPayment(DebtPartialPaymentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['date'] = now();

        $payment = DebtPayment::create($data);

        $payment->clientDeliveryDebt->updateStatusBasedOnPayments();

        return response()->json($payment->load('debts'), 200);
    }

    public function show(DebtPayment $debtPayment): JsonResponse
    {
        return response()->json($debtPayment->load('debts'), 200);
    }
}
