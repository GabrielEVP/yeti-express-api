<?php

namespace App\Debt\Controllers;

use App\Core\Controllers\Controller;
use App\Debt\DTO\DebtPaymentDTO;
use App\Debt\DTO\FormRequestFullPaymentDTO;
use App\Debt\DTO\FormRequestPartialPaymentDTO;
use App\Debt\DTO\FormRequestPayAllDTO;
use App\Debt\DTO\FormRequestPayPartialDTO;
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
        $dto = FormRequestFullPaymentDTO::fromArray($request->validated());
        $service = $this->service->storeFullPayment($dto);

        return response()->json($service, 201);
    }

    public function storePartialPayment(DebtPartialPaymentRequest $request): JsonResponse
    {
        $dto = FormRequestPartialPaymentDTO::fromArray($request->validated());
        $service = $this->service->storePartialPayment($dto);

        return response()->json($service, 201);
    }

    public function payAllDebts(PayAllDebtsRequest $request): JsonResponse
    {
        $dto = FormRequestPayAllDTO::fromArray($request->validated());
        $this->service->payAllDebtsForClient($dto);

        return response()->json(null, 204);
    }

    public function payPartialAmount(PayPartialAmountRequest $request): JsonResponse
    {
        $dto = FormRequestPayPartialDTO::fromArray($request->validated());
        $this->service->payPartialAmountForClient($dto);

        return response()->json(null, 204);
    }

    public function show(DebtPayment $debtPayment): JsonResponse
    {
        $payment = DebtPaymentDTO::fromModel($debtPayment->load('debt'));
        return response()->json($payment, 200);
    }
}
