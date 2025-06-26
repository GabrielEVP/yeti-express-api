<?php

namespace App\Client\Services;

use App\Client\DTO\ClientDTO;
use App\Client\DTO\FilterClientDTO;
use App\Client\DTO\FilterClientPaginatedDTO;
use App\Client\DTO\FilterRequestClientDTO;
use App\Client\DTO\SimpleClientDTO;
use App\Client\Models\Client;
use App\Client\Repositories\IClientRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientService implements IClientRepository
{

    private const SELECT_SIMPLE_FIELDS = ['id', 'legal_name', 'type', 'registration_number'];

    private function baseQuery()
    {
        return Client::query()
            ->where('user_id', Auth::id());
    }

    protected array $validSortColumns = [
        "id",
        "registration_number",
        "legal_name",
        "type",
        "country",
        "tax_rate",
        "allow_credit"
    ];

    public function all(): Collection
    {
        return $this->baseQuery()
            ->select(self::SELECT_SIMPLE_FIELDS)
            ->selectRaw('
                NOT EXISTS (SELECT 1 FROM deliveries WHERE deliveries.client_id = id) as can_delete
            ')
            ->get()
            ->map(fn($row) => new SimpleClientDTO(json_decode(json_encode($row), true)));
    }

    public function find(string $id): ClientDTO
    {
        $client = $this->baseQuery()->with('events', 'phones', 'emails', 'addresses')->findOrFail($id);
        return new ClientDTO($client);
    }

    public function create(array $data): SimpleClientDTO
    {
        $client = Auth::user()->clients()->create($data);

        foreach ($data['addresses'] ?? [] as $address) {
            $client->addresses()->create($address);
        }

        foreach ($data['emails'] ?? [] as $email) {
            $client->emails()->create($email);
        }

        foreach ($data['phones'] ?? [] as $phone) {
            $client->phones()->create($phone);
        }

        return new SimpleClientDTO($client);
    }

    public function update(string $id, array $data): SimpleClientDTO
    {
        $client = $this->baseQuery()->findOrFail($id);
        $client->update($data);

        $client->addresses()->delete();

        foreach ($data['addresses'] ?? [] as $address) {
            $client->addresses()->create($address);
        }

        $client->emails()->delete();

        foreach ($data['emails'] ?? [] as $email) {
            $client->emails()->create($email);
        }

        $client->phones()->delete();

        foreach ($data['phones'] ?? [] as $phone) {
            $client->phones()->create($phone);
        }

        return new SimpleClientDTO($client);
    }

    public function delete(string $id): void
    {
        $client = $this->baseQuery()->findOrFail($id);

        if ($client->deliveries()->exists()) {
            throw new \Exception('client_has_delivery_relation');
        }

        $client->delete();
    }

    public function search(string $query): Collection
    {
        return $this->baseQuery()
            ->select(self::SELECT_SIMPLE_FIELDS)
            ->selectRaw('
                NOT EXISTS (SELECT 1 FROM deliveries WHERE deliveries.client_id = id) as can_delete
            ')
            ->where(function ($q) use ($query) {
                $q->where('legal_name', 'like', "%{$query}%")
                    ->orWhere('registration_number', 'like', "%{$query}%");
            })
            ->get()
            ->map(fn($row) => new SimpleClientDTO(json_decode(json_encode($row), true)));
    }


    public function filter(FilterRequestClientDTO $filterRequestClientDTO): FilterClientPaginatedDTO
    {
        $sort = $filterRequestClientDTO->sortBy === 'legalName' ? 'legal_name' : $filterRequestClientDTO->sortBy;
        $order = $filterRequestClientDTO->sortDirection;

        if (!in_array($sort, $this->validSortColumns) || !in_array($order, ['asc', 'desc'])) {
            throw new \InvalidArgumentException('Invalid sort parameters');
        }

        $query = DB::table('clients')
            ->where('user_id', Auth::id())
            ->when($filterRequestClientDTO->search !== '', function ($q) use ($filterRequestClientDTO) {
                $q->where('legal_name', 'LIKE', "%{$filterRequestClientDTO->search}%")
                    ->orWhere('registration_number', 'LIKE', "%{$filterRequestClientDTO->search}%");
            })
            ->when($filterRequestClientDTO->type !== null, fn($q) => $q->where('type', $filterRequestClientDTO->type))
            ->when($filterRequestClientDTO->allowCredit !== null, fn($q) => $q->where('allow_credit', $filterRequestClientDTO->allowCredit))
            ->when(!empty($filterRequestClientDTO->select), function ($q) use ($filterRequestClientDTO) {
                foreach ($filterRequestClientDTO->select as $filter) {
                    if (
                        isset($filter['option'], $filter['value']) &&
                        in_array($filter['option'], $this->validSortColumns)
                    ) {
                        $q->where($filter['option'], $filter['value']);
                    }
                }
            })
            ->orderBy($sort, $order);

        $paginator = $query->paginate($filterRequestClientDTO->perPage, ['*'], 'page', $filterRequestClientDTO->page);

        $items = $paginator->getCollection()->map(function ($client) {
            $client->can_delete = !DB::table('deliveries')->where('client_id', $client->id)->exists();
            $client->has_had_debt = DB::table('debts')->where('client_id', $client->id)->exists();
            return new FilterClientDTO((array)$client);
        });

        return new FilterClientPaginatedDTO(
            $items,
            $paginator->currentPage(),
            $paginator->perPage(),
            $paginator->total()
        );
    }
}
