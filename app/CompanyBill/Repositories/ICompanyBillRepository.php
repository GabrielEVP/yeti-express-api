<?php

namespace App\CompanyBill\Repositories;

use Illuminate\Support\Collection;
use App\CompanyBill\Models\CompanyBill;

interface ICompanyBillRepository
{
    public function all(): Collection;
    public function find(string $id): CompanyBill;
    public function create(array $data): CompanyBill;
    public function update(string $id, array $data): CompanyBill;
    public function delete(string $id): void;
    public function search(string $query): Collection;
}

