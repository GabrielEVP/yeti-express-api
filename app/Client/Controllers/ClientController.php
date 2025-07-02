<?php

namespace App\Client\Controllers;

use App\Client\DTO\FilterRequestClientPaginatedDTO;
use App\Client\DTO\FormRequestClientDTO;
use App\Client\Request\ClientRequest;
use App\Client\Services\ClientService;
use App\Core\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    private ClientService $service;

    public function __construct(ClientService $service)
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

    public function store(ClientRequest $request): JsonResponse
    {
        $dto = FormRequestClientDTO::fromArray($request->validated());
        $service = $this->service->create($dto->toArray());

        return response()->json($service, 201);
    }


    public function update(string $id, ClientRequest $request): JsonResponse
    {
        $dto = FormRequestClientDTO::fromArray($request->validated());
        $service = $this->service->update($id, $dto->toArray());

        return response()->json($service, 200);
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

    public function filter(Request $request): JsonResponse
    {
        $filters = $request->input('filters', []);

        $filterDTO = new FilterRequestClientPaginatedDTO(
            $request->string('search')->toString(),
            $request->input('sortBy', 'legal_name'),
            $request->input('sortDirection', 'asc'),
            $filters['type'] ?? null,
            isset($filters['allowCredit']) ? (bool)$filters['allowCredit'] : null,
            $request->input('select', []),
            $request->integer('page', 1),
            $request->integer('perPage', 15)
        );

        $clients = $this->service->filter($filterDTO);

        return response()->json($clients);
    }
}
