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
    public function index(): JsonResponse
    {
        $couriers = Auth::user()->couriers()->get();
        return response()->json($couriers, 200);
    }

    public function show(Courier $courier): JsonResponse
    {
        $this->authorizeOwner($courier);
        return response()->json($courier, 200);
    }

    public function store(CourierRequest $request): JsonResponse
    {
        $data = $request->all();
        $data['user_id'] = Auth::id();

        $courier = Auth::user()->couriers()->create($data);
        return response()->json($courier, 201);
    }

    public function update(CourierRequest $request, Courier $courier): JsonResponse
    {
        $this->authorizeOwner($courier);

        $data = $request->all();
        $data['user_id'] = Auth::id();
        $courier->update($data);

        CourierEvent::create([
            'event' => "update_courier",
            'reference_table' => null,
            'reference_id' => null,
            'client_id' => $courier->id,
        ]);

        return response()->json($courier, 200);

    }



    public function destroy(Courier $courier): JsonResponse
    {
        $this->authorizeOwner($courier);
        $courier->delete();
        return response()->json([
            'message' => "Courier with ID {$courier->id} has been deleted"
        ], 200);
    }

    private function authorizeOwner(Courier $courier): void
    {
        abort_if($courier->user_id !== Auth::id(), 403, 'No tienes permiso para acceder a este mensajero.');
    }
}
