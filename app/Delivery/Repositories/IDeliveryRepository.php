<?php

namespace App\Delivery\Repositories;

use App\Delivery\DTO\DeliveryDTO;
use App\Delivery\DTO\FilterDeliveryPaginatedDTO;
use App\Delivery\DTO\FilterRequestDeliveryDTO;
use Illuminate\Support\Collection;

interface IDeliveryRepository
{
    public function all(): Collection;

    public function find(string $id): DeliveryDTO;

    public function create(array $data): DeliveryDTO;

    public function update(string $id, array $data): DeliveryDTO;

    public function delete(string $id): void;

    public function search(string $query): Collection;

    public function filter(FilterRequestDeliveryDTO $filterRequestDeliveryDTO): FilterDeliveryPaginatedDTO;

    public function updateStatus(string $id, string $status): void;

    public function cancelDelivery(string $id, string $query): void;


}
