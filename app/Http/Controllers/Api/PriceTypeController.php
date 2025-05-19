<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PriceTypeRequest;
use App\Models\PriceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PriceTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $priceTypes = Auth::user()->priceTypes()->get();

        return response()->json($priceTypes, 200);
    }

    public function show(PriceType $priceType): JsonResponse
    {
        $this->authorizeOwner($priceType);

        return response()->json($priceType, 200);
    }

    public function store(PriceTypeRequest $request): JsonResponse
    {
        $priceType = Auth::user()->priceTypes()->create($request->validated());

        return response()->json($priceType, 201);
    }

    public function update(PriceTypeRequest $request, PriceType $priceType): JsonResponse
    {
        $this->authorizeOwner($priceType);

        $priceType->update($request->validated());

        return response()->json($priceType, 200);
    }

    public function destroy(PriceType $priceType): JsonResponse
    {
        $this->authorizeOwner($priceType);

        $priceType->delete();

        return response()->json([
            'message' => "Price type with ID {$priceType->id} has been deleted"
        ], 200);
    }

    private function authorizeOwner(PriceType $priceType): void
    {
        abort_if($priceType->user_id !== Auth::id(), 403, 'No tienes permiso para acceder a este tipo de precio.');
    }
}
