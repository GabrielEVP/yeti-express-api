<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Http\Requests\ServiceRequest;

class ServiceController extends Controller
{
    private array $relations = ['bills', 'events'];

    public function index(Request $request): JsonResponse
    {
        $service = Service::with($this->relations);
        return response()->json($service->get(), 200);
    }

    public function store(ServiceRequest $request): JsonResponse
    {
        $service = Service::create(
            $request->merge(['user_id' => $request->user()->id])
                ->only(['name', 'description', 'amount', 'comision', 'user_id'])
        );

        foreach ($request->input('bills', []) as $bill) {
            $service->bills()->create($bill);
        }

        return response()->json($service->load($this->relations), 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(Service::with($this->relations)->findOrFail($id), 200);
    }

    public function update(ServiceRequest $request, string $id): JsonResponse
    {
        $service = Service::findOrFail($id);

        $service->update(
            $request->merge(['user_id' => $request->user()->id])
                ->only(['name', 'description', 'amount', 'comision', 'user_id'])
        );

        $service->bills()->delete();
        foreach ($request->input('bills', []) as $bill) {
            $service->bills()->create($bill);
        }

        ServiceEvent::create([
            "event" => "update_service",
            "section" => "services",
            "reference_table" => null,
            "reference_id" => null,
            "service_id" => $service->id,
        ]);

        return response()->json($service->load($this->relations), 200);
    }

    public function destroy(string $id): JsonResponse
    {
        $service = Service::findOrFail($id);

        $service->bills()->delete();
        $service->delete();

        return response()->json([
            'message' => "Service with ID {$id} has been deleted"
        ], 200);
    }

    public function getByDelivery(string $delivery_id): JsonResponse
    {
        return response()->json(
            Service::where('courier_id', $delivery_id)->with($this->relations)->get(),
            200
        );
    }

    public function search(string $query): JsonResponse
    {
        return response()->json(
            Service::with($this->relations)->where('name', 'LIKE', "%{$query}%")->get(),
            200
        );
    }
}