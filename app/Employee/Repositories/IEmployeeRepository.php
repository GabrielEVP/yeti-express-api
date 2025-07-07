<?php

namespace App\Employee\Repositories;

use App\Core\DTO\FilterRequestPaginatedDTO;
use App\Core\DTO\PaginatedDTO;
use App\Employee\DTO\EmployeeDTO;

interface IEmployeeRepository
{
    public function all(): array;

    public function find(string $id): EmployeeDTO;

    public function create(array $data): EmployeeDTO;

    public function update(string $id, array $data): EmployeeDTO;

    public function updatePassword(string $id, string $password): EmployeeDTO;

    public function delete(string $id): void;

    public function filter(FilterRequestPaginatedDTO $filters): PaginatedDTO;
}
