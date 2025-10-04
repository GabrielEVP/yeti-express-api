<?php

namespace App\Delivery\Services;

use App\Client\Models\Client;
use App\Delivery\DTO\{DeliveryDTO, FilterDeliveryPaginatedDTO, FilterRequestDeliveryPaginatedDTO, SimpleDeliveryDTO};
use App\Delivery\Models\Delivery;
use App\Delivery\Models\PaymentStatus;
use App\Delivery\Models\PaymentType;
use App\Delivery\Models\Status;
use App\Delivery\Repositories\IDeliveryRepository;
use App\Service\Models\Service;
use App\Shared\Services\AuthHelper;
use App\Shared\Services\EmployeeEventService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class DeliveryService implements IDeliveryRepository
{
    private const SELECT_SIMPLE_FIELDS = ['id', 'number', 'date', 'amount', 'status'];

    private function baseQuery()
    {
        return Delivery::query()
            ->where('deliveries.user_id', AuthHelper::getUserId());
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
            'receipt',
            'courier:id,first_name,last_name',
            'client:id,id,legal_name',
            'client.phones' => function ($query) {
                $query->select('client_id', 'phone')->limit(1);
            },
            'anonymousClient',
            'service:id,name',
        ])->findOrFail($id);

        return new DeliveryDTO($delivery);
    }

    public function create(array $data): DeliveryDTO
    {
        if (!isset($data['payment_type']) || empty($data['payment_type'])) {
            $data['payment_type'] = 'full';
        }
        $data['number'] = $this->generateNumberDelivery();
        $data['amount'] = $this->getServiceAmount($data['service_id']);
        if (isset($data['client_id']) && (empty($data['client_id']))) {
            $data['client_id'] = null;
        }
        $data['user_id'] = AuthHelper::getUserId();
        $delivery = Delivery::create($data);
        if (isset($data['anonymous_client'])) {
            $delivery->anonymousClient()->create($data['anonymous_client']);
        }
        if (isset($data['receipt'])) {
            $delivery->receipt()->create($data['receipt']);
        }
        EmployeeEventService::log(
            'create_delivery',
            'deliveries',
            'deliveries',
            (int) $delivery->id,
            'Delivery created: ' . $delivery->number
        );
        return new DeliveryDTO($delivery);
    }

    public function update(string $id, array $data): DeliveryDTO
    {
        if (!isset($data['payment_type']) || empty($data['payment_type'])) {
            $data['payment_type'] = 'full';
        }
        $delivery = $this->baseQuery()->findOrFail($id);
        if (isset($data['client_id']) && (empty($data['client_id']) || $data['client_id'] == '0' || $data['client_id'] == 0)) {
            $data['client_id'] = null;
        }
        if (isset($data['service_id'])) {
            $data['amount'] = $this->getServiceAmount($data['service_id']);
        }
        $delivery->update($data);
        if (isset($data['anonymous_client'])) {
            $delivery->anonymousClient()->update($data['anonymous_client']);
        }
        if (isset($data['receipt'])) {
            $delivery->receipt()->update($data['receipt']);
        }
        EmployeeEventService::log(
            'update_delivery',
            'deliveries',
            'deliveries',
            (int) $id,
            'Delivery updated: ' . $delivery->number
        );
        return new DeliveryDTO($delivery);
    }

    public function delete(string $id): void
    {
        $delivery = $this->baseQuery()->findOrFail($id);
        $number = $delivery->number;
        $delivery->delete();

        EmployeeEventService::log(
            'delete_delivery',
            'deliveries',
            'deliveries',
            (int) $id,
            'Delivery deleted: ' . $number
        );
    }

    public function filter(FilterRequestDeliveryPaginatedDTO $filterRequestDeliveryDTO): FilterDeliveryPaginatedDTO
    {
        $sort = $filterRequestDeliveryDTO->sortBy;
        $order = $filterRequestDeliveryDTO->sortDirection;

        if (!in_array($sort, $this->validSortColumns) || !in_array($order, ['asc', 'desc'])) {
            throw new InvalidArgumentException('Invalid sort parameters');
        }

        $query = $this->baseQuery()
            ->select(['deliveries.id as id', 'deliveries.number as number', 'deliveries.date as date', 'deliveries.amount as amount', 'deliveries.status as status', 'deliveries.payment_status as payment_status', 'deliveries.client_id as client_id'])
            ->selectRaw('
                COALESCE(clients.legal_name, delivery_anonymous_clients.legal_name) as client_name,
                CASE
                    WHEN clients.legal_name IS NOT NULL THEN "client"
                    WHEN delivery_anonymous_clients.legal_name IS NOT NULL THEN "anonymous"
                    ELSE "none"
                END as client_name_source,
                services.name as service_name,
                CONCAT(couriers.first_name, " ", couriers.last_name) as courier_full_name
            ')
            ->leftJoin('clients', 'deliveries.client_id', '=', 'clients.id')
            ->leftJoin('delivery_anonymous_clients', 'deliveries.id', '=', 'delivery_anonymous_clients.delivery_id')
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

        if ($delivery->status === Status::DELIVERED) {
            if ($delivery->payment_type === PaymentType::PARTIAL) {
                $this->createDebtToDelivery($delivery);
            } elseif ($delivery->payment_type === PaymentType::FULL) {
                $delivery->payment_status = PaymentStatus::PAID;
            }
        }

        $delivery->save();

        EmployeeEventService::log(
            'update_delivery_status',
            'deliveries',
            'deliveries',
            (int) $id,
            'Delivery status updated to ' . $status->value . ': ' . $delivery->number
        );
    }

    public function cancelDelivery(string $id, string $cancellation_notes): void
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->status = Status::CANCELLED;
        $delivery->cancellation_notes = $cancellation_notes;
        $delivery->save();

        EmployeeEventService::log(
            'cancel_delivery',
            'deliveries',
            'deliveries',
            (int) $id,
            'Delivery cancelled: ' . $delivery->number
        );
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
        if (empty($clientId)) {
            return PaymentType::FULL->value;
        }

        $client = Client::findOrFail($clientId);
        return $client->allow_credit ? PaymentType::PARTIAL->value : PaymentType::FULL->value;
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
