<?php

namespace App\Delivery\Services;

use App\Client\Models\Client;
use App\Delivery\DTO\{DeliveryDTO, FilterDeliveryPaginatedDTO, FilterRequestDeliveryPaginatedDTO, SimpleDeliveryDTO};
use App\Delivery\Models\Delivery;
use App\Delivery\Models\Status;
use App\Delivery\Repositories\IDeliveryRepository;
use App\Service\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DeliveryService implements IDeliveryRepository
{
    private const SELECT_SIMPLE_FIELDS = ['id', 'number', 'date', 'amount', 'status'];

    private function baseQuery()
    {
        return Delivery::query()
            ->where('deliveries.user_id', Auth::id());
    }

    protected array $validSortColumns = [
        "id",
        "number",
        "date",
        "amount",
        "status",
    ];

    public function all(): Collection
    {
        return $this->baseQuery()
            ->select(self::SELECT_SIMPLE_FIELDS)
            ->get()
            ->map(fn($row) => new SimpleDeliveryDTO(json_decode(json_encode($row), true)));
    }

    public function find(string $id): DeliveryDTO
    {
        $delivery = $this->baseQuery()->with([
            'events',
            'receipt',
            'courier',
            'client:id,legal_name',
            'courier:id,first_name,last_name',
            'service:id,name',
        ])->findOrFail($id);

        return new DeliveryDTO($delivery);
    }

    public function create(array $data): DeliveryDTO
    {
        $data['date'] = now()->toDateString();
        $data['number'] = $this->generateNumberDelivery();
        $data['amount'] = $this->getServiceAmount($data['service_id']);

        if (empty($data['payment_type'])) {
            $data['payment_type'] = $this->getAllowCreditClient($data['client_id']);
        }

        $delivery = Auth::user()->deliveries()->create($data);

        if (isset($data['receipt'])) {
            $delivery->receipt()->create($data['receipt']);
        }

        return new DeliveryDTO($delivery);
    }

    public function update(string $id, array $data): DeliveryDTO
    {
        $delivery = $this->baseQuery()->findOrFail($id);
        $delivery->update($data);

        if (isset($data['receipt'])) {
            $delivery->receipt()->update($data['receipt']);
        }

        return new DeliveryDTO($delivery);
    }

    public function delete(string $id): void
    {
        $delivery = $this->baseQuery()->findOrFail($id);
        $delivery->delete();
    }

    public function filter(FilterRequestDeliveryPaginatedDTO $filterRequestDeliveryDTO): FilterDeliveryPaginatedDTO
    {
        $sort = $filterRequestDeliveryDTO->sortBy;
        $order = $filterRequestDeliveryDTO->sortDirection;

        if (!in_array($sort, $this->validSortColumns) || !in_array($order, ['asc', 'desc'])) {
            throw new \InvalidArgumentException('Invalid sort parameters');
        }

        $query = $this->baseQuery()
            ->select(['deliveries.id as id', 'deliveries.number as number', 'deliveries.date as date', 'deliveries.amount as amount', 'deliveries.status as status', 'deliveries.payment_status as payment_status'])
            ->selectRaw('
                clients.legal_name as client_name,
                services.name as service_name,
                CONCAT(couriers.first_name, " ", couriers.last_name) as courier_full_name
            ')
            ->leftJoin('clients', 'deliveries.client_id', '=', 'clients.id')
            ->leftJoin('services', 'deliveries.service_id', '=', 'services.id')
            ->leftJoin('couriers', 'deliveries.courier_id', '=', 'couriers.id')
            ->when($filterRequestDeliveryDTO->search !== '', function ($q) use ($filterRequestDeliveryDTO) {
                $q->where(function ($query) use ($filterRequestDeliveryDTO) {
                    $query->where('deliveries.number', 'LIKE', "%{$filterRequestDeliveryDTO->search}%");
                });
            })
            ->when($filterRequestDeliveryDTO->start_date !== null, function ($q) use ($filterRequestDeliveryDTO) {
                $q->whereDate('deliveries.date', '>=', $filterRequestDeliveryDTO->start_date);
            })
            ->when($filterRequestDeliveryDTO->end_date !== null, function ($q) use ($filterRequestDeliveryDTO) {
                $q->whereDate('deliveries.date', '<=', $filterRequestDeliveryDTO->end_date);
            })
            ->when(
                $filterRequestDeliveryDTO->status !== null,
                fn($q) => $q->where('deliveries.status', $filterRequestDeliveryDTO->status),
                fn($q) => $q->whereIn('deliveries.status', ['pending', 'in_transit'])
            )
            ->when($filterRequestDeliveryDTO->service_id !== null, fn($q) => $q->where('deliveries.service_id', $filterRequestDeliveryDTO->service_id))
            ->when($filterRequestDeliveryDTO->payment_status !== null, fn($q) => $q->where('deliveries.payment_status', $filterRequestDeliveryDTO->payment_status))
            ->orderBy('deliveries.' . $sort, $order);

        $paginator = $query->paginate(
            $filterRequestDeliveryDTO->perPage ?? 15,
            ['*'],
            'page',
            $filterRequestDeliveryDTO->page ?? 1
        );

        $data = $paginator->getCollection()->map(function ($delivery) {
            return new SimpleDeliveryDTO($delivery->toArray());
        });

        return new FilterDeliveryPaginatedDTO(
            $data,
            $paginator->currentPage(),
            $paginator->perPage(),
            $paginator->total()
        );
    }

    public function updateStatus(string $id, Status $status): void
    {
        $delivery = Delivery::findOrFail($id);

        $delivery->status = $status;

        if ($status === Status::DELIVERED) {
            if ($delivery->payment_type === 'partial') {
                $this->createDebtToDelivery($delivery);
            } elseif ($delivery->payment_type === 'full') {
                $delivery->payment_status = 'paid';
            }
        }

        $delivery->save();
    }

    public function cancelDelivery(string $id, string $cancellation_notes): void
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->status = Status::CANCELLED;
        $delivery->cancellation_notes = $cancellation_notes;
        $delivery->save();
    }

    private function generateNumberDelivery(): string
    {
        return 'DEV-' . str_pad((Delivery::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT);
    }

    private function getServiceAmount(string $serviceId): float
    {
        $service = Service::findOrFail($serviceId);
        return $service->amount;
    }

    private function getAllowCreditClient(string $clientId): string
    {
        $client = Client::findOrFail($clientId);
        return $client->allow_credit ? 'partial' : 'full';
    }

    private function CreateDebtToDelivery(Delivery $delivery): void
    {
        $delivery->debt()->create([
            'amount' => $delivery->amount,
            'status' => 'pending',
            'client_id' => $delivery->client_id,
            'delivery_id' => $delivery->id,
            'user_id' => Auth::id(),
        ]);
    }
}
