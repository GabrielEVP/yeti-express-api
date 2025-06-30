<?php

namespace App\Debt\Controllers;

use App\Debt\Models\DebtPayment;
use App\Debt\Request\DebtFullPaymentRequest;
use App\Debt\Request\DebtPartialPaymentRequest;
use App\Debt\Services\DebtPaymentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebtPaymentController extends Controller
{
    private DebtPaymentService $service;

    public function __construct(DebtPaymentService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        return response()->json($this->service->getAll(), 200);
    }

    public function storeFullPayment(DebtFullPaymentRequest $request): JsonResponse
    {
        $payment = $this->service->storeFullPayment($request->input('debt_id'), $request->input('method'));
        return response()->json($payment, 201);
    }

    public function storePartialPayment(DebtPartialPaymentRequest $request): JsonResponse
    {
        $payment = $this->service->storePartialPayment(
            $request->input('debt_id'),
            $request->input('amount'),
            $request->input('method')
        );
        return response()->json($payment, 201);
    }

    public function payAllDebts(Request $request): JsonResponse
    {
        $payData = $request->input('pay');
        $payments = $this->service->payAllDebtsForClient($payData['clientId'], $payData['method']);
        return response()->json($payments, 201);
    }

    public function payPartialAmount(Request $request): JsonResponse
    {
        $payData = $request->input('pay');
        $payments = $this->service->payPartialAmountForClient(
            $payData['clientId'],
            $payData['amount'],
            $payData['method']
        );

        return response()->json($payments, 201);
    }

    public function show(DebtPayment $debtPayment): JsonResponse
    {
        return response()->json($debtPayment->load('debt'), 200);
    }
}
