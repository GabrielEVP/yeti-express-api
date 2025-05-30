<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Http\Requests\ServiceRequest;

class ServiceController extends Controller
{
    private array $relations = ['bills'];

    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $sort = $request->input('sort.column', 'name');
        $order = strtolower($request->input('sort.order', 'asc'));

        $validColumns = ['id', 'name', 'amount', 'comision'];

        if (!in_array($sort, $validColumns) || !in_array($order, ['asc', 'desc'])) {
            return response()->json(['error' => 'Invalid sort parameters'], 400);
        }

        $query = Service::with($this->relations)
            ->when($search, fn($q) => $q->where('name', 'LIKE', "%{$search}%"))
            ->when($request->has('select'), function ($q) use ($request, $validColumns) {
                foreach ($request->input('select', []) as $filter) {
                    if (
                        isset($filter['option'], $filter['value']) &&
                        in_array($filter['option'], $validColumns)
                    ) {
                        $q->where($filter['option'], $filter['value']);
                    }
                }
            })
            ->orderBy($sort, $order);

        return response()->json($query->get(), 200);
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