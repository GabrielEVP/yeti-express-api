<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Http\Requests\ServiceRequest;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->query("search", "");
        $sort = $request->query("sort")["column"] ?? "name";
        $order = strtolower($request->query("sort")["order"] ?? "asc");

        $validColumns = ["id", "name", "amount", "comision"];

        if (!in_array($sort, $validColumns)) {
            return response()->json(["error" => "Invalid sortBy column"], 400);
        }

        if (!in_array($order, ["asc", "desc"])) {
            return response()->json(["error" => "Invalid order value"], 400);
        }

        $query = Service::with("bills");

        if (!empty($search)) {
            $query->where("name", "LIKE", "%{$search}%");
        }

        if ($request->has("select")) {
            foreach ($request->query("select") as $filter) {
                if (
                    !empty($filter["option"]) &&
                    !empty($filter["value"]) &&
                    in_array($filter["option"], $validColumns)
                ) {
                    $query->where($filter["option"], $filter["value"]);
                }
            }
        }

        $query->orderBy($sort, $order);
        $services = $query->get();

        return response()->json($services, 200);
    }

    public function store(ServiceRequest $request): JsonResponse
    {
        $data = $request->only(["name", "description", "amount", "comision"]);
        $data['user_id'] = $request->user()->id;
        $service = Service::create($data);

        $bills = $request->input("bills", []);
        foreach ($bills as $bill) {
            $service->bills()->create($bill);
        }

        return response()->json($service->load("bills"), 201);
    }

    public function show(string $id): JsonResponse
    {
        $service = Service::with("bills")->findOrFail($id);
        return response()->json($service, 200);
    }

    public function update(ServiceRequest $request, string $id): JsonResponse
    {
        $service = Service::findOrFail($id);
        $service->update(
            array_merge(
                $request->only(["name", "description", "amount", "comision"]),
                ['user_id' => $request->user()->id]
            )
        );

        $service->bills()->delete();
        foreach ($request->input("bills", []) as $bill) {
            $service->bills()->create($bill);
        }

        return response()->json($service->load("bills"), 200);
    }

    public function destroy(string $id): JsonResponse
    {
        $service = Service::findOrFail($id);
        $service->bills()->delete();
        $service->delete();

        return response()->json(
            ["message" => "Service With Id: {$id} Has Been Deleted"],
            200
        );
    }

    public function getByDelivery(string $delivery_id): JsonResponse
    {
        $service = Service::where('courier_id', $delivery_id)->get();
        return response()->json($service, 200);
    }

    public function search(string $query): JsonResponse
    {
        $services = Service::with("bills")
            ->where("name", "LIKE", "%{$query}%")
            ->get();

        return response()->json($services, 200);
    }
}
