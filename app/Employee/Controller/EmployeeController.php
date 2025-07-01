<?php

namespace App\Employee\Controller;

use App\Core\Controllers\Controller;
use App\Employee\DTO\FormRequestCreateEmployeeDTO;
use App\Employee\DTO\FormRequestUpdateEmployeeDTO;
use App\Employee\DTO\FormRequestUpdatePassword;
use App\Employee\Requests\EmployeeRequest;
use App\Employee\Services\EmployeeService;
use Illuminate\Http\JsonResponse;

class EmployeeController extends Controller
{
    private EmployeeService $service;

    public function __construct(EmployeeService $service)
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

    public function store(EmployeeRequest $request): JsonResponse
    {
        $dto = FormRequestCreateEmployeeDTO::fromArray($request->validated());
        $service = $this->service->create($dto->toArray());

        return response()->json($service, 201);
    }

    public function update(EmployeeRequest $request, string $id): JsonResponse
    {
        $dto = FormRequestUpdateEmployeeDTO::fromArray($request->validated());
        $service = $this->service->update($id, $dto->toArray());

        return response()->json($service, 201);
    }

    public function updatePassword(EmployeeRequest $request, string $id): JsonResponse
    {
        $password = FormRequestUpdatePassword::fromArray($request->validated());
        $service = $this->service->updatePassword($id, $password->password);

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
