<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\ClientEvent;
use App\Http\Requests\ClientRequest;
use Carbon\Carbon;
use App\Http\Services\EmployeeEventService;

class ClientController extends Controller
{
    private array $relations = [
        "addresses",
        "phones",
        "emails",
        "events",
    ];

    public function index(): JsonResponse
    {
        $clients = Auth::user()->clients()->with('addresses')->get();
        return response()->json($clients, 200);
    }

    public function store(ClientRequest $request): JsonResponse
    {
        $client = Auth::user()->clients()->create($request->all());

        $this->syncRelations($client, $request);

        EmployeeEventService::log(
            'create_client',
            'clients',
            'clients',
            $client->id
        );

        return response()->json($client->load($this->relations), 200);
    }

    public function show(string $id): JsonResponse
    {
        $client = Auth::user()->clients()->with($this->relations)->findOrFail($id);
        return response()->json($client, 200);
    }

    public function update(ClientRequest $request, string $id): JsonResponse
    {
        $client = Auth::user()->clients()->findOrFail($id);
        $client->update($request->all());

        $this->syncRelations($client, $request);

        ClientEvent::create([
            "event" => "update_client",
            "section" => "clients",
            "reference_table" => null,
            "reference_id" => null,
            "client_id" => $client->id,
        ]);

        EmployeeEventService::log(
            'update_client',
            'clients',
            'clients',
            $client->id
        );

        return response()->json($client->load($this->relations), 200);
    }

    public function destroy(string $id): JsonResponse
    {
        $client = Auth::user()->clients()->findOrFail($id);

        if ($client->deliveries()->exists()) {
            return response()->json([
                "error" => "Cannot delete client. It has associated deliveries."
            ], 422);
        }

        $client->addresses()->delete();
        $client->phones()->delete();
        $client->emails()->delete();
        $client->delete();

        EmployeeEventService::log(
            'delete_client',
            'clients',
            'clients',
            $client->id
        );

        return response()->json(
            ["message" => "Client with ID {$id} has been deleted"],
            200
        );
    }

    public function search(string $query): JsonResponse
    {
        return response()->json(
            Auth::user()->clients()->with($this->relations)
                ->where("legal_name", "LIKE", "%{$query}%")
                ->get(),
            200
        );
    }

    public function filter(Request $request): JsonResponse
    {
        $search = $request->string("search")->toString();
        $sort = $request->input("sortBy", "legal_name");
        $order = strtolower($request->input("sortDirection", "asc"));

        $validColumns = [
            "id",
            "registration_number",
            "legal_name",
            "type",
            "country",
            "tax_rate",
            "allow_credit"
        ];

        if ($sort === 'legalName') {
            $sort = 'legal_name';
        }

        if (
            !in_array($sort, $validColumns) ||
            !in_array($order, ["asc", "desc"])
        ) {
            return response()->json(
                ["error" => "Invalid sort parameters"],
                400
            );
        }

        $query = Auth::user()->clients()->when(
            $search,
            fn($q) => $q->where("legal_name", "LIKE", "%{$search}%")
        )
            ->when($request->has("type"), function ($q) use ($request) {
                $q->where("type", $request->input("type"));
            })
            ->when($request->has("allowCredit"), function ($q) use ($request) {
                $q->where("allow_credit", $request->boolean("allowCredit"));
            })
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

        $clients = $query->get();

        $clients->each(function ($client) {
            $client->can_delete = !$client->deliveries()->exists();
            $client->has_had_debt = $client->debts()->exists();
        });

        return response()->json($clients, 200);
    }
    public function getTotalInvoiced(string $id): JsonResponse
    {
        $client = Auth::user()->clients()->findOrFail($id);
        $total = $client->deliveries()->sum('amount');

        return response()->json($total, 200);
    }

    public function getEarningsDelivery(string $id): JsonResponse
    {
        $client = Auth::user()->clients()->findOrFail($id);
        $earnings = $client->deliveries()
            ->where('payment_status', 'PAID')
            ->sum('amount');

        return response()->json($earnings, 200);
    }

    public function getPendingEarnings(string $id): JsonResponse
    {
        $client = Auth::user()->clients()->findOrFail($id);
        $pending = $client->deliveries()
            ->where('payment_status', '!=', 'PAID')
            ->sum('amount');

        return response()->json($pending, 200);
    }

    public function getPendingEarningsCount(string $id): JsonResponse
    {
        $client = Auth::user()->clients()->findOrFail($id);
        $pending = $client->deliveries()->where('payment_status', '!=', 'PAID')->count();
        return response()->json($pending, 200);
    }

    private function syncRelations(Client $client, Request $request): void
    {
        $this->syncGenericRelation($client, "addresses");
        $this->syncGenericRelation($client, "phones");
        $this->syncGenericRelation($client, "emails");
    }

    private function syncGenericRelation(Client $client, string $relation, callable $deleteConstraint = null): void
    {
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