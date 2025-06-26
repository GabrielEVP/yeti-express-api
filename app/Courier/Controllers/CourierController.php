<?php

namespace App\Courier\Controllers;

use App\Courier\DTO\FormRequestCourierDTO;
use App\Courier\Requests\CourierRequest;
use App\Courier\Services\CourierService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CourierController extends Controller
{
    private CourierService $service;

    public function __construct(CourierService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        return response()->json($this->service->all(), 200);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->service->find($id));
    }

    public function store(CourierRequest $request): JsonResponse
    {
        $dto = FormRequestCourierDTO::fromArray($request->validated());
        $service = $this->service->create($dto->toArray());

        return response()->json($service, 201);
    }

    public function update(CourierRequest $request, string $id): JsonResponse
    {
        $dto = FormRequestCourierDTO::fromArray($request->validated());
        $service = $this->service->update($id, $dto->toArray());

        return response()->json($service, 201);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function search(string $query): JsonResponse
    {
        return response()->json($this->service->search($query), 200);
    }
}
