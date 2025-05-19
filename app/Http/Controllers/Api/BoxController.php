<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BoxRequest;
use App\Models\Box;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BoxController extends Controller
{
    public function index(): JsonResponse
    {
        $boxes = Auth::user()
            ->boxes()
            ->with(['openDeliveries', 'closeDeliveries'])
            ->get();

        return response()->json($boxes, 200);
    }

    public function show(Box $box): JsonResponse
    {
        $this->authorizeBoxOwner($box);

        return response()->json(
            $box->load(['openDeliveries', 'closeDeliveries']),
            200
        );
    }

    public function store(BoxRequest $request): JsonResponse
    {
        $box = Auth::user()->boxes()->create($request->validated());

        return response()->json(
            $box->load(['openDeliveries', 'closeDeliveries']),
            201
        );
    }

    public function update(BoxRequest $request, Box $box): JsonResponse
    {
        $this->authorizeBoxOwner($box);

        $box->update($request->validated());

        return response()->json(
            $box->load(['openDeliveries', 'closeDeliveries']),
            200
        );
    }

    public function destroy(Box $box): JsonResponse
    {
        $this->authorizeBoxOwner($box);
        $box->delete();

        return response()->json([
            'message' => "Box with ID {$box->id} has been deleted"
        ], 200);
    }

    private function authorizeBoxOwner(Box $box): void
    {
        abort_if($box->user_id !== Auth::id(), 403, 'Unauthorized access to this box.');
    }
}
