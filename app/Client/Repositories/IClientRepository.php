<?php

namespace App\Client\Repositories;

use App\Client\DTO\ClientDTO;
use App\Client\DTO\FilterClientPaginatedDTO;
use App\Client\DTO\FilterRequestClientDTO;
use App\Client\DTO\SimpleClientDTO;
use Illuminate\Support\Collection;

interface IClientRepository
{
    public function all(): Collection;

    public function find(string $id): ClientDTO;

    public function create(array $data): SimpleClientDTO;

    public function update(string $id, array $data): SimpleClientDTO;

    public function delete(string $id): void;

    public function search(string $query): Collection;

    public function filter(FilterRequestClientDTO $filterRequestClientDTO): FilterClientPaginatedDTO;
}
