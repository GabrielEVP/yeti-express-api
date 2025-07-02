<?php

namespace App\Client\Repositories;

use App\Client\DTO\ClientDTO;
use App\Client\DTO\FilterClientPaginatedDTO;
use App\Client\DTO\FilterRequestClientPaginatedDTO;
use Illuminate\Support\Collection;

interface IClientRepository
{
    public function all(): Collection;

    public function find(string $id): ClientDTO;

    public function create(array $data): ClientDTO;

    public function update(string $id, array $data): ClientDTO;

    public function delete(string $id): void;

    public function filter(FilterRequestClientPaginatedDTO $filterRequestClientDTO): FilterClientPaginatedDTO;
}
