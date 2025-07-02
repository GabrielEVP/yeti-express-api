<?php

namespace App\Courier\Controllers;

use App\Core\Controllers\Controller;
use App\Core\DTO\FilterRequestPaginatedDTO;
use App\Courier\DTO\FormRequestCourierDTO;
use App\Courier\Requests\CourierRequest;
use App\Courier\Services\CourierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function filter(Request $request): JsonResponse
    {
        $filterDTO = new FilterRequestPaginatedDTO(
            $request->string('search')->toString(),
            $request->input('sortBy', 'first_name'),
            $request->input('sortDirection', 'asc'),
            $request->integer('page', 1),
            $request->integer('perPage', 15)
        );

        $couriers = $this->service->filter($filterDTO);

        return response()->json($couriers);
    }
}
