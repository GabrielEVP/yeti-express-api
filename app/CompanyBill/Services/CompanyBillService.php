<?php

namespace App\CompanyBill\Services;

use App\CompanyBill\Repositories\ICompanyBillRepository;
use App\CompanyBill\Models\CompanyBill;
use Illuminate\Support\Facades\Auth;
use \Illuminate\Support\Collection;

class CompanyBillService implements ICompanyBillRepository
{
    private const SELECT_SIMPLE_FIELDS = ['id', 'name', 'date', 'method', 'amount'];

    private function baseQuery()
    {
        return CompanyBill::query()
            ->where('user_id', Auth::id());
    }

    public function all(): Collection
    {
        return $this->baseQuery()
            ->select(self::SELECT_SIMPLE_FIELDS)
            ->get();
    }

    public function create(array $data): CompanyBill
    {
        return CompanyBill::create($data);
    }

    public function find(string $id): CompanyBill
    {
        return $this->baseQuery()->findOrFail($id);
    }

    public function update(string $id, array $data): CompanyBill
    {
        $bill = $this->find($id);
        $bill->update($data);
        return $bill;
    }

    public function delete(string $id): void
    {
        $this->find($id)->delete();
    }

    public function search(string $query): Collection
    {
        return $this->baseQuery()
            ->select(self::SELECT_SIMPLE_FIELDS)
            ->where('name', 'like', "%{$query}%")
            ->get();
    }
}
