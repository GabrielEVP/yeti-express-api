<?php

namespace App\CompanyBill\Controllers;

use App\Http\Controllers\Controller;
use App\CompanyBill\Services\CompanyBillService;
use App\CompanyBill\DTO\CompanyBillDTO;
use App\Http\Requests\CompanyBillRequest;
use App\Http\Services\EmployeeEventService;
use Illuminate\Http\JsonResponse;

class CompanyBillController extends Controller
{
    public function __construct(
        protected CompanyBillService $service
    ) {}

    public function index(): JsonResponse
    {
        $bills = $this->service->all()->map(
            fn ($bill) => new CompanyBillDTO($bill)
        );

        return response()->json($bills, 200);
    }

    public function store(CompanyBillRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $bill = $this->service->create($data);

        return response()->json(new CompanyBillDTO($bill), 201);
    }

    public function show(string $id): JsonResponse
    {
        $bill = $this->service->find($id);
        return response()->json(new CompanyBillDTO($bill), 200);
    }

    public function update(CompanyBillRequest $request, string $id): JsonResponse
    {
        $bill = $this->service->update($id, $request->validated());

        EmployeeEventService::log('update_company_bill', 'companyBills', 'companyBills', $bill->id);

        return response()->json(new CompanyBillDTO($bill), 200);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        EmployeeEventService::log('delete_company_bill', 'companyBills', 'companyBills', $id);

        return response()->json([
            'message' => "CompanyBill with ID {$id} has been deleted",
        ], 200);
    }

    public function search(string $query): JsonResponse
    {
        $bills = $this->service->search($query)->map(
            fn ($bill) => new CompanyBillDTO($bill)
        );

        return response()->json($bills, 200);
    }
}
