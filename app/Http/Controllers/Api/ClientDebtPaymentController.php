<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientDebtPaymentRequest;
use App\Models\ClientDebtPayment;
use Illuminate\Http\JsonResponse;

class ClientDebtPaymentController extends Controller
{
    public function index(): JsonResponse
    {
        $payments = ClientDebtPayment::with('clientDeliveryDebt')->get();

        return response()->json($payments, 200);
    }

    public function store(ClientDebtPaymentRequest $request): JsonResponse
    {
        $payment = ClientDebtPayment::create($request->validated());

        return response()->json($payment->load('clientDeliveryDebt'), 201);
    }

    public function show(ClientDebtPayment $clientDebtPayment): JsonResponse
    {
        return response()->json($clientDebtPayment->load('clientDeliveryDebt'), 200);
    }

    public function update(ClientDebtPaymentRequest $request, ClientDebtPayment $clientDebtPayment): JsonResponse
    {
        $clientDebtPayment->update($request->validated());

        return response()->json($clientDebtPayment->load('clientDeliveryDebt'), 200);
    }

    public function destroy(ClientDebtPayment $clientDebtPayment): JsonResponse
    {
        $clientDebtPayment->delete();

        return response()->json(['message' => 'Payment deleted successfully'], 200);
    }
}
