<?php

namespace App\Employee\Controllers;

use App\Core\Controllers\Controller;
use App\Core\DTO\FilterRequestPaginatedDTO;
use App\Employee\DTO\FormRequestCreateEmployeeDTO;
use App\Employee\DTO\FormRequestUpdateEmployeeDTO;
use App\Employee\DTO\FormRequestUpdatePassword;
use App\Employee\Requests\EmployeeRequest;
use App\Employee\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function filter(Request $request): JsonResponse
    {
        $filterDTO = new FilterRequestPaginatedDTO(
            $request->string('search')->toString(),
            $request->input('sortBy', 'name'),
            $request->input('sortDirection', 'asc'),
            $request->integer('page', 1),
            $request->integer('perPage', 15)
        );

        $employees = $this->service->filter($filterDTO);

        return response()->json($employees);
    }
}
