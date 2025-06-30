<?php

namespace App\Courier\Services;

use App\Courier\DTO\CourierDTO;
use App\Courier\DTO\SimpleCourierDTO;
use App\Courier\Models\Courier;
use App\Courier\Repositories\ICourierRepository;
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
            ->map(fn($row) => new SimpleCourierDTO(json_decode(json_encode($row), true)));
    }

    public function find(string $id): CourierDTO
    {
        $courier = $this->baseQuery()->with('events')->findOrFail($id);
        return new CourierDTO($courier);
    }

    public function create(array $data): CourierDTO
    {
        $courier = Auth::user()->couriers()->create($data);
        return new CourierDTO($courier);
    }

    public function update(string $id, array $data): CourierDTO
    {
        $courier = Courier::findOrFail($id);
        $courier->update($data);

        return new CourierDTO($courier);
    }

    public function delete(string $id): void
    {
        $courier = Courier::findOrFail($id);

        if ($courier->deliveries()->exists()) {
            throw new \Exception('courier_has_delivery_relation');
        }

        $courier->delete();
    }

    public function search(string $query): Collection
    {
        return $this->baseQuery()
            ->select(self::SELECT_SIMPLE_FIELDS)
            ->selectRaw('
                NOT EXISTS (SELECT 1 FROM deliveries WHERE deliveries.courier_id = id) as can_delete
            ')
            ->where('first_name', 'like', "%{$query}%")
            ->orWhere('last_name', 'like', "%{$query}%")
            ->get()
            ->map(fn($row) => new SimpleCourierDTO(json_decode(json_encode($row), true)));
    }
}
