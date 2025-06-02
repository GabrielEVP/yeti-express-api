<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\CompanyBill;
use App\Http\Requests\CompanyBillRequest;

class CompanyBillController extends Controller
{
    private array $relations = ["user"];

    public function index(Request $request): JsonResponse
    {
        $search = $request->string("search")->toString();
        $sort = $request->input("sort.column", "date");
        $order = strtolower($request->input("sort.order", "desc"));

        $validColumns = ["id", "name", "date", "amount", "method"];

        if (
            !in_array($sort, $validColumns) ||
            !in_array($order, ["asc", "desc"])
        ) {
            return response()->json(
                ["error" => "Invalid sort parameters"],
                400
            );
        }

        $query = CompanyBill::with($this->relations)
            ->when($search, fn($q) => $q->where("name", "LIKE", "%{$search}%"))
            ->when($request->has("select"), function ($q) use (
                $request,
                $validColumns
            ) {
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

    public function store(CompanyBillRequest $request): JsonResponse
    {
        $bill = CompanyBill::create(
            $request
                ->merge(["user_id" => $request->user()->id])
                ->only([
                    "date",
                    "name",
                    "description",
                    "method",
                    "amount",
                    "user_id",
                ])
        );

        return response()->json($bill->load($this->relations), 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(
            CompanyBill::with($this->relations)->findOrFail($id),
            200
        );
    }

    public function update(
        CompanyBillRequest $request,
        string $id
    ): JsonResponse {
        $bill = CompanyBill::findOrFail($id);

        $bill->update(
            $request
                ->merge(["user_id" => $request->user()->id])
                ->only([
                    "date",
                    "name",
                    "description",
                    "method",
                    "amount",
                    "user_id",
                ])
        );

        return response()->json($bill->load($this->relations), 200);
    }

    public function destroy(string $id): JsonResponse
    {
        $bill = CompanyBill::findOrFail($id);
        $bill->delete();

        return response()->json(
            [
                "message" => "CompanyBill with ID {$id} has been deleted",
            ],
            200
        );
    }

    public function search(string $query): JsonResponse
    {
        return response()->json(
            CompanyBill::with($this->relations)
                ->where("name", "LIKE", "%{$query}%")
                ->get(),
            200
        );
    }
}
