<?php

namespace App\Debt\Controllers;

use App\Core\Controllers\Controller;
use App\Debt\DTO\DebtPaymentDTO;
use App\Debt\DTO\FullPaymentRequestDTO;
use App\Debt\DTO\PartialPaymentRequestDTO;
use App\Debt\Models\DebtPayment;
use App\Debt\Requests\DebtFullPaymentRequest;
use App\Debt\Requests\DebtPartialPaymentRequest;
use App\Debt\Requests\PayAllDebtsRequest;
use App\Debt\Requests\PayPartialAmountRequest;
use App\Debt\Services\DebtPaymentService;
use Illuminate\Http\JsonResponse;

class DebtPaymentController extends Controller
{
    private DebtPaymentService $service;

    public function __construct(DebtPaymentService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $payments = $this->service->getAll();
        return response()->json($payments, 200);
    }

    public function storeFullPayment(DebtFullPaymentRequest $request): JsonResponse
    {
        $paymentRequest = new FullPaymentRequestDTO(
            debt_id: $request->input('debt_id'),
            method: $request->input('method')
        );

        $payment = $this->service->storeFullPayment($paymentRequest);
        return response()->json($payment, 201);
    }

    public function storePartialPayment(DebtPartialPaymentRequest $request): JsonResponse
    {
        $paymentRequest = new PartialPaymentRequestDTO(
            debt_id: $request->input('debt_id'),
            amount: (float)$request->input('amount'),
            method: $request->input('method')
        );

        $payment = $this->service->storePartialPayment($paymentRequest);
        return response()->json($payment, 201);
    }

    public function payAllDebts(PayAllDebtsRequest $request): JsonResponse
    {
        $paymentRequest = $request->toDTO();
        $payments = $this->service->payAllDebtsForClient($paymentRequest);
        return response()->json($payments, 201);
    }

    public function payPartialAmount(PayPartialAmountRequest $request): JsonResponse
    {
        $paymentRequest = $request->toDTO();
        $payments = $this->service->payPartialAmountForClient($paymentRequest);
        return response()->json($payments, 201);
    }

    public function show(DebtPayment $debtPayment): JsonResponse
    {
        $payment = DebtPaymentDTO::fromModel($debtPayment->load('debt'));
        return response()->json($payment, 200);
    }
}
