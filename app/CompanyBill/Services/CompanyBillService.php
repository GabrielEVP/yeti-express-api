<?php

namespace App\CompanyBill\Services;

use App\CompanyBill\Repositories\ICompanyBillRepository;
use App\Models\CompanyBill;

class CompanyBillService implements ICompanyBillRepository
{
    public function all(): \Illuminate\Support\Collection
    {
        return CompanyBill::all();
    }

    public function create(array $data): CompanyBill
    {
        return CompanyBill::create($data);
    }

    public function find(string $id): CompanyBill
    {
        return CompanyBill::findOrFail($id);
    }

    public function update(string $id, array $data): CompanyBill
    {
        $bill = CompanyBill::findOrFail($id);
        $bill->update($data);
        return $bill;
    }

    public function delete(string $id): void
    {
        $bill = CompanyBill::findOrFail($id);
        $bill->delete();
    }

    public function search(string $query): \Illuminate\Support\Collection
    {
        return CompanyBill::where('name', 'like', "%{$query}%")->get()->all();
    }
}
