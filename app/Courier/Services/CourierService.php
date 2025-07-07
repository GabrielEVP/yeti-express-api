<?php

namespace App\Courier\Services;

use App\Core\DTO\FilterRequestPaginatedDTO;
use App\Core\DTO\PaginatedDTO;
use App\Courier\DTO\CourierDTO;
use App\Courier\DTO\SimpleCourierDTO;
use App\Courier\Models\Courier;
use App\Courier\Repositories\ICourierRepository;
use App\Shared\Services\EmployeeEventService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CourierService implements ICourierRepository
{
    private const SELECT_SIMPLE_FIELDS = ['id', 'first_name', 'last_name', 'phone'];

    private function baseQuery()
    {
        return Courier::query()
            ->where('user_id', Auth::id());
    }

    public function all(): Collection
    {
        return $this->baseQuery()
            ->select(self::SELECT_SIMPLE_FIELDS)
            ->selectRaw('
                NOT EXISTS (SELECT 1 FROM deliveries WHERE deliveries.courier_id = id) as can_delete
            ')
            ->get()
            ->map(fn($row) => SimpleCourierDTO::mapFromArray($row));
    }

    public function find(string $id): CourierDTO
    {
        $courier = $this->baseQuery()->findOrFail($id);
        return new CourierDTO($courier);
    }

    public function create(array $data): CourierDTO
    {
        $courier = Auth::user()->couriers()->create($data);

        EmployeeEventService::log(
            'create_courier',
            'couriers',
            'couriers',
            (int)$courier->id,
            'Courier created: ' . $courier->first_name . ' ' . $courier->last_name
        );

        return new CourierDTO($courier);
    }

    public function update(string $id, array $data): CourierDTO
    {
        $courier = Courier::findOrFail($id);
        $courier->update($data);

        EmployeeEventService::log(
            'update_courier',
            'couriers',
            'couriers',
            (int)$id,
            'Courier updated: ' . $courier->first_name . ' ' . $courier->last_name
        );

        return new CourierDTO($courier);
    }

    public function delete(string $id): void
    {
        $courier = Courier::findOrFail($id);

        if ($courier->deliveries()->exists()) {
            throw new \Exception('courier_has_delivery_relation');
        }

        $courierName = $courier->first_name . ' ' . $courier->last_name;
        $courier->delete();

        EmployeeEventService::log(
            'delete_courier',
            'couriers',
            'couriers',
            (int)$id,
            'Courier deleted: ' . $courierName
        );
    }

    public function filter(FilterRequestPaginatedDTO $filters): PaginatedDTO
    {
        $query = $this->baseQuery()
            ->select(self::SELECT_SIMPLE_FIELDS)
            ->selectRaw('
                NOT EXISTS (SELECT 1 FROM deliveries WHERE deliveries.courier_id = id) as can_delete
            ')
            ->when($filters->search !== '', function ($q) use ($filters) {
                $q->where(function ($query) use ($filters) {
                    $query->where('first_name', 'LIKE', "%{$filters->search}%")
                        ->orWhere('last_name', 'LIKE', "%{$filters->search}%")
                        ->orWhere('phone', 'LIKE', "%{$filters->search}%");
                });
            })
            ->orderBy($filters->sortBy, $filters->sortDirection);

        $paginator = $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );

        $items = $paginator->getCollection()->map(fn($courier) => SimpleCourierDTO::mapFromArray($courier));

        return new PaginatedDTO(
            $items,
            $paginator->currentPage(),
            $paginator->perPage(),
            $paginator->total()
        );
    }
}
