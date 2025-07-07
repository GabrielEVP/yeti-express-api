<?php

namespace App\Courier\Repositories;

use App\Core\DTO\FilterRequestPaginatedDTO;
use App\Core\DTO\PaginatedDTO;
use App\Courier\DTO\CourierDTO;
use Illuminate\Support\Collection;

interface ICourierRepository
{
    public function all(): Collection;

    public function find(string $id): CourierDTO;

    public function create(array $data): CourierDTO;

    public function update(string $id, array $data): CourierDTO;

    public function delete(string $id): void;

    public function filter(FilterRequestPaginatedDTO $filters): PaginatedDTO;
}
