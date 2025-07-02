<?php

namespace App\Delivery\Controllers;

use App\Core\Controllers\Controller;
use App\Delivery\DTO\FilterRequestDeliveryPaginatedDTO;
use App\Delivery\DTO\FormRequestDeliveryDTO;
use App\Delivery\Models\Status;
use App\Delivery\Request\DeliveryRequest;
use App\Delivery\Request\DeliveryStatusRequest;
use App\Delivery\Services\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    private DeliveryService $service;

    public function __construct(DeliveryService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        return response()->json($this->service->all(), 200);
    }

    public function store(DeliveryRequest $request): JsonResponse
    {
        $dto = FormRequestDeliveryDTO::fromArray($request->validated());
        $service = $this->service->create($dto->toArray());

        return response()->json($service, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->service->find($id));
    }

    public function update(string $id, DeliveryRequest $request): JsonResponse
    {
        $dto = FormRequestDeliveryDTO::fromArray($request->validated());
        $service = $this->service->update($id, $dto->toArray());
        return response()->json($service, 200);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function filter(Request $request): JsonResponse
    {
        $filters = $request->input('filters', []);

        $filterDTO = new FilterRequestDeliveryPaginatedDTO(
            search: $request->string('search')->toString(),
            sortBy: $request->input('sortBy', 'number'),
            sortDirection: $request->input('sortDirection', 'asc'),
            status: $filters['status'] ?? null,
            service_id: $filters['serviceId'] ?? null,
            payment_status: $filters['paymentStatus'] ?? null,
            start_date: $filters['startDate'] ?? null,
            end_date: $filters['endDate'] ?? null,
            page: $request->integer('page', 1),
            perPage: $request->integer('perPage', 15)
        );


        $clients = $this->service->filter($filterDTO);

        return response()->json($clients);
    }

    public function updateStatus(string $id, DeliveryStatusRequest $request): JsonResponse
    {
        $status = Status::from($request->input('status'));
        $this->service->updateStatus($id, $status);
        return response()->json(null, 204);

    }

    public function cancelDelivery(string $id, Request $request): JsonResponse
    {
        $this->service->cancelDelivery($id, $request->input('cancellation_notes'));
        return response()->json(null, 204);
    }
}
