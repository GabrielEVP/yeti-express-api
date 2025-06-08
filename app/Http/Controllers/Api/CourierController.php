<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourierRequest;
use App\Models\Courier;
use App\Models\CourierEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CourierController extends Controller
{
    private array $relations = [
        'events',
        'deliveries',
        'deliveries.service',
        'deliveries.courier',
        'deliveries.receipt',
    ];

    public function index(): JsonResponse
    {
        return response()->json(Auth::user()->couriers()->get(), 200);
    }

    public function show(Courier $courier): JsonResponse
    {
        $this->authorizeOwner($courier);
        return response()->json($courier->load($this->relations), 200);
    }

    public function store(CourierRequest $request): JsonResponse
    {
        $courier = Auth::user()->couriers()->create($request->merge(['user_id' => Auth::id()])->all());

        return response()->json($courier, 201);
    }

    public function update(CourierRequest $request, Courier $courier): JsonResponse
    {
        $this->authorizeOwner($courier);
        $courier->update($request->merge(['user_id' => Auth::id()])->all());

        CourierEvent::create([
            'event' => 'update_courier',
            'section' => 'couriers',
            'reference_table' => null,
            'reference_id' => null,
            'courier_id' => $courier->id,
        ]);

        return response()->json($courier, 200);
    }

    public function destroy(Courier $courier): JsonResponse
    {
        $this->authorizeOwner($courier);
        $courier->delete();

        return response()->json([
            'message' => "Courier with ID {$courier->id} has been deleted",
        ], 200);
    }

    private function authorizeOwner(Courier $courier): void
    {
        abort_if(
            $courier->user_id !== Auth::id(),
            403,
            'You do not have permission to access this courier.'
        );
    }

    public function search(string $query): JsonResponse
    {
        return response()->json(
            Courier::with($this->relations)
                ->where('first_name', 'LIKE', "%{$query}%")
                ->orWhere('last_name', 'LIKE', "%{$query}%")
                ->get(),
            200
        );
    }
}