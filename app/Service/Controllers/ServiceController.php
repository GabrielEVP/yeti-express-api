<?php

namespace App\Service\Controllers;

use App\Core\Controllers\Controller;
use App\Service\DTO\FormRequestServiceDTO;
use App\Service\Repositories\IServiceRepository;
use App\Service\Requests\ServiceRequest;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    private IServiceRepository $service;

    public function __construct(IServiceRepository $service)
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

    public function store(ServiceRequest $request): JsonResponse
    {
        $dto = FormRequestServiceDTO::fromArray($request->validated());
        $service = $this->service->create($dto->toArray());

        return response()->json($service, 201);
    }

    public function update(ServiceRequest $request, string $id): JsonResponse
    {
        $dto = FormRequestServiceDTO::fromArray($request->validated());
        $service = $this->service->update($id, $dto->toArray());

        return response()->json($service);
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
