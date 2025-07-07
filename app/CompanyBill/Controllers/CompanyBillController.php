<?php

namespace App\CompanyBill\Controllers;

use App\CompanyBill\DTO\CompanyBillDTO;
use App\CompanyBill\DTO\FormRequestCompanyBillDTO;
use App\CompanyBill\DTO\SimpleCompanyBillDTO;
use App\CompanyBill\Requests\CompanyBillRequest;
use App\CompanyBill\Services\CompanyBillService;
use App\Core\Controllers\Controller;
use App\Core\DTO\FilterRequestPaginatedDTO;
use App\Shared\Services\EmployeeEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyBillController extends Controller
{
    public function __construct(protected CompanyBillService $CompanyBillService)
    {
    }

    public function index(): JsonResponse
    {
        $bills = $this->CompanyBillService->all()->map(
            fn($bill) => new SimpleCompanyBillDTO($bill)
        );

        return response()->json($bills, 200);
    }

    public function store(CompanyBillRequest $request): JsonResponse
    {
        $data = FormRequestCompanyBillDTO::fromArray($request->validated());
        $bill = $this->CompanyBillService->create($data->toArray());

        EmployeeEventService::log('create_company_bill', 'companyBills', 'companyBills', $bill->id);

        return response()->json(new CompanyBillDTO($bill), 201);
    }

    public function show(string $id): JsonResponse
    {
        $bill = $this->CompanyBillService->find($id);
        return response()->json(new CompanyBillDTO($bill), 200);
    }

    public function update(CompanyBillRequest $request, string $id): JsonResponse
    {
        $data = FormRequestCompanyBillDTO::fromArray($request->validated());
        $bill = $this->CompanyBillService->update($id, $data->toArray());

        EmployeeEventService::log('update_company_bill', 'companyBills', 'companyBills', $id);

        return response()->json(new CompanyBillDTO($bill), 200);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->CompanyBillService->delete($id);

        EmployeeEventService::log('delete_company_bill', 'companyBills', 'companyBills', $id);

        return response()->json(['message' => "CompanyBill with ID {$id} has been deleted"], 200);
    }

    public function filter(Request $request): JsonResponse
    {
        $filterDTO = new FilterRequestPaginatedDTO(
            $request->string('search')->toString(),
            $request->input('sortBy', 'date'),
            $request->input('sortDirection', 'desc'),
            $request->integer('page', 1),
            $request->integer('perPage', 15)
        );

        $bills = $this->CompanyBillService->filter($filterDTO);

        return response()->json($bills);
    }
}
