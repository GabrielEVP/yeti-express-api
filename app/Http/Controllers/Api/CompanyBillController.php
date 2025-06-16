<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\CompanyBill;
use App\Http\Requests\CompanyBillRequest;
use App\Http\Services\EmployeeEventService;
use Illuminate\Support\Facades\Auth;

class CompanyBillController extends Controller
{
    private array $relations = [];

    public function index(Request $request): JsonResponse
    {
        $search = $request->string("search")->toString();
        $validColumns = ["id", "name", "date", "amount", "method"];

        $query = Auth::user()->companyBills()->with($this->relations)
            ->when($search, fn($q) => $q->where("name", "LIKE", "%{$search}%"))
            ->when($request->has("select"), function ($q) use ($request, $validColumns) {
                foreach ($request->input("select", []) as $filter) {
                    if (
                        isset($filter["option"], $filter["value"]) &&
                        in_array($filter["option"], $validColumns)
                    ) {
                        $q->where($filter["option"], $filter["value"]);
                    }
                }
            });

        return response()->json($query->get(), 200);
    }

    public function store(CompanyBillRequest $request): JsonResponse
    {
        $data = $request->merge(["user_id" => $request->user()->id])->only([
            "date",
            "name",
            "description",
            "method",
            "amount",
            "user_id",
        ]);

        if (isset($data['date'])) {
            $data['date'] = date('Y-m-d', strtotime($data['date']));
        }

        $bill = CompanyBill::create($data);


        return response()->json($bill->load($this->relations), 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(
            CompanyBill::with($this->relations)->findOrFail($id),
            200
        );
    }

    public function update(CompanyBillRequest $request, string $id): JsonResponse
    {
        $bill = CompanyBill::findOrFail($id);

        $data = $request->merge(["user_id" => $request->user()->id])->only([
            "date",
            "name",
            "description",
            "method",
            "amount",
            "user_id",
        ]);

        if (isset($data['date'])) {
            $data['date'] = date('Y-m-d', strtotime($data['date']));
        }

        EmployeeEventService::log(
            'update_company_bill',
            'companyBills',
            'companyBills',
            $bill->id
        );

        $bill->update($data);

        return response()->json($bill->load($this->relations), 200);
    }

    public function destroy(string $id): JsonResponse
    {
        $bill = CompanyBill::findOrFail($id);
        $bill->delete();

        EmployeeEventService::log(
            'delete_company_bill',
            'companyBills',
            'companyBills',
            $bill->id
        );

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
