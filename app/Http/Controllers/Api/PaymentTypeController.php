<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentTypeRequest;
use App\Models\PaymentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PaymentTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $paymentTypes = Auth::user()->paymentTypes()->get();

        return response()->json($paymentTypes, 200);
    }

    public function show(PaymentType $paymentType): JsonResponse
    {
        $this->authorizeOwner($paymentType);

        return response()->json($paymentType, 200);
    }

    public function store(PaymentTypeRequest $request): JsonResponse
    {
        $paymentType = Auth::user()->paymentTypes()->create($request->validated());

        return response()->json($paymentType, 201);
    }

    public function update(PaymentTypeRequest $request, PaymentType $paymentType): JsonResponse
    {
        $this->authorizeOwner($paymentType);

        $paymentType->update($request->validated());

        return response()->json($paymentType, 200);
    }

    public function destroy(PaymentType $paymentType): JsonResponse
    {
        $this->authorizeOwner($paymentType);

        $paymentType->delete();

        return response()->json([
            'message' => "Payment type with ID {$paymentType->id} has been deleted"
        ], 200);
    }

    private function authorizeOwner(PaymentType $paymentType): void
    {
        abort_if($paymentType->user_id !== Auth::id(), 403, 'No tienes permiso para acceder a este tipo de pago.');
    }
}
