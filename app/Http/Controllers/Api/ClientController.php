<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\ClientEvent;
use App\Http\Requests\ClientRequest;

class ClientController extends Controller
{
    private array $relations = [
        "addresses",
        "phones",
        "emails",
        "deliveries",
        "deliveries.service",
        "debts",
        "events",
    ];

    public function index(Request $request): JsonResponse
    {
        $search = $request->string("search")->toString();
        $sort = $request->input("sort.column", "legal_name");
        $order = strtolower($request->input("sort.order", "asc"));

        $validColumns = [
            "id",
            "registration_number",
            "legal_name",
            "type",
            "country",
            "tax_rate",
        ];

        if (
            !in_array($sort, $validColumns) ||
            !in_array($order, ["asc", "desc"])
        ) {
            return response()->json(
                ["error" => "Invalid sort parameters"],
                400
            );
        }

        $query = Client::with($this->relations)
            ->when(
                $search,
                fn($q) => $q->where("legal_name", "LIKE", "%{$search}%")
            )
            ->when($request->has("select"), function ($q) use ($request, $validColumns) {
                foreach ($request->input("select", []) as $filter) {
                    if (
                        isset($filter["option"], $filter["value"]) &&
                        in_array($filter["option"], $validColumns)
                    ) {
                        $q->where($filter["option"], $filter["value"]);
                    }
                }
            })
            ->orderBy($sort, $order);

        return response()->json($query->get(), 200);
    }

    public function store(ClientRequest $request): JsonResponse
    {
        $client = Client::create(
            $request->merge(["user_id" => Auth::id()])->all()
        );

        $this->syncRelations($client, $request);

        return response()->json($client->load($this->relations), 200);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(
            Client::with($this->relations)->findOrFail($id),
            200
        );
    }

    public function update(ClientRequest $request, string $id): JsonResponse
    {
        $client = Client::findOrFail($id);
        $client->update($request->merge(["user_id" => Auth::id()])->all());

        $this->syncRelations($client, $request);

        ClientEvent::create([
            "event" => "update_client",
            "section" => "clients",
            "reference_table" => null,
            "reference_id" => null,
            "client_id" => $client->id,
        ]);

        return response()->json($client->load($this->relations), 200);
    }

    public function destroy(string $id): JsonResponse
    {
        $client = Client::findOrFail($id);

        $client->addresses()->delete();
        $client->phones()->delete();
        $client->emails()->delete();
        $client->delete();

        return response()->json(
            ["message" => "Client with ID {$id} has been deleted"],
            200
        );
    }

    public function search(string $query): JsonResponse
    {
        return response()->json(
            Client::with($this->relations)
                ->where("legal_name", "LIKE", "%{$query}%")
                ->get(),
            200
        );
    }

    private function syncRelations(Client $client, Request $request): void
    {
        $this->syncGenericRelation(
            $client,
            "addresses",
            fn($q) => $q->doesntHave("deliveries")
        );
        $this->syncGenericRelation($client, "phones");
        $this->syncGenericRelation($client, "emails");
    }

    private function syncGenericRelation(
        Client $client,
        string $relation,
        callable $deleteConstraint = null
    ): void {
        $data = collect(request()->input($relation, []));
        $idsToKeep = $data->pluck("id")->filter()->toArray();

        $query = $client->{$relation}()->whereNotIn("id", $idsToKeep);
        if ($deleteConstraint) {
            $query = $deleteConstraint($query);
        }
        $query->delete();

        foreach ($data as $item) {
            if (
                !empty($item["id"]) &&
                ($record = $client->{$relation}()->find($item["id"]))
            ) {
                $record->update($item);
            } else {
                $client->{$relation}()->create($item);
            }
        }
    }
}
