<?php

namespace App\CompanyBill\Repositories;

use App\CompanyBill\Models\CompanyBill;
use App\Core\DTO\FilterRequestPaginatedDTO;
use App\Core\DTO\PaginatedDTO;
use Illuminate\Support\Collection;

interface ICompanyBillRepository
{
    public function all(): Collection;

    public function find(string $id): CompanyBill;

    public function create(array $data): CompanyBill;

    public function update(string $id, array $data): CompanyBill;

    public function delete(string $id): void;

    public function filter(FilterRequestPaginatedDTO $filters): PaginatedDTO;
}

