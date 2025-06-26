<?php

namespace App\Service\Services;

use App\Service\DTO\ServiceDTO;
use App\Service\DTO\SimpleServiceDTO;
use App\Service\Models\Service;
use App\Service\Models\ServiceEvent;
use App\Service\Repositories\IServiceRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class Services implements IServiceRepository
{
    private function baseQuery()
    {
        return Service::query()
            ->where('user_id', Auth::id());
    }

    public function all(): Collection
    {
        return $this->baseQuery()
            ->leftJoin('bills', 'services.id', '=', 'bills.service_id')
            ->select('services.id', 'services.name', 'services.amount')
            ->selectRaw('
                COALESCE(SUM(bills.amount), 0) as total_expense,
                services.amount - COALESCE(SUM(bills.amount), 0) as total_earning,
                NOT EXISTS (SELECT 1 FROM deliveries WHERE deliveries.service_id = services.id) as can_delete
            ')
            ->groupBy('services.id', 'services.name', 'services.amount')
            ->get()
            ->map(fn($row) => new SimpleServiceDTO(json_decode(json_encode($row), true)));
    }

    public function find(string $id): ServiceDTO
    {
        $service = $this->baseQuery()
            ->leftJoin('bills', 'services.id', '=', 'bills.service_id')
            ->select('services.*')
            ->selectRaw('COALESCE(SUM(bills.amount), 0) as total_expense')
            ->selectRaw('services.amount - COALESCE(SUM(bills.amount), 0) as total_earning')
            ->where('services.id', $id)
            ->groupBy('services.id', 'services.name', 'services.description', 'services.amount', 'services.user_id', 'services.created_at', 'services.updated_at')
            ->firstOrFail();

        return new ServiceDTO($service);
    }

    public function create(array $data): ServiceDTO
    {
        $service = Auth::user()->services()->create($data);

        foreach ($data['bills'] ?? [] as $bill) {
            $service->bills()->create($bill);
        }

        return new ServiceDTO($service);
    }

    public function update(string $id, array $data): ServiceDTO
    {
        $service = Service::findOrFail($id);

        $service->update($data);
        $service->bills()->delete();

        foreach ($data['bills'] ?? [] as $bill) {
            $service->bills()->create($bill);
        }

        ServiceEvent::create([
            'event' => 'update_service',
            'section' => 'services',
            'reference_table' => null,
            'reference_id' => null,
            'service_id' => $service->id,
        ]);

        return new ServiceDTO($service);
    }

    public function delete(string $id): void
    {
        $service = Service::findOrFail($id);

        if ($service->deliveries()->exists()) {
            throw new \Exception('service_has_delivery_relation');
        }

        $service->bills()->delete();
        $service->delete();
    }

    public function search(string $query): Collection
    {
        return $this->baseQuery()
            ->leftJoin('bills', 'services.id', '=', 'bills.service_id')
            ->select('services.id', 'services.name', 'services.amount')
            ->selectRaw('
                COALESCE(SUM(bills.amount), 0) as total_expense,
                services.amount - COALESCE(SUM(bills.amount), 0) as total_earning,
                NOT EXISTS (SELECT 1 FROM deliveries WHERE deliveries.service_id = services.id) as can_delete
            ')
            ->where('services.name', 'like', "%{$query}%")
            ->groupBy('services.id', 'services.name', 'services.amount')
            ->get()
            ->map(fn($row) => new SimpleServiceDTO(json_decode(json_encode($row), true)));
    }
}

