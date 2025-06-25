<?php

namespace App\Service\Repositories;

use App\Service\DTO\ServiceDTO;
use Illuminate\Support\Collection;

interface IServiceRepository
{
    public function all(): Collection;

    public function find(string $id): ServiceDTO;

    public function create(array $data): ServiceDTO;

    public function update(string $id, array $data): ServiceDTO;

    public function delete(string $id): void;

    public function search(string $query): Collection;
}


