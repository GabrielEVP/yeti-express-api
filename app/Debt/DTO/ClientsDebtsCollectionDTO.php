<?php

namespace App\Debt\DTO;

use Countable;
use Illuminate\Support\Collection;
use JsonSerializable;

class ClientsDebtsCollectionDTO implements JsonSerializable, Countable
{
    /**
     * @var ClientDebtsDTO[]
     */
    private array $clients;

    public function __construct(array $clients)
    {
        $this->clients = $clients;
    }

    public function count(): int
    {
        return count($this->clients);
    }

    public function isEmpty(): bool
    {
        return empty($this->clients);
    }

    public function getClients(): array
    {
        return $this->clients;
    }

    public static function fromCollection(Collection $clients): self
    {
        $clientDTOs = $clients->map(function ($client) {
            return ClientDebtsDTO::fromModel($client);
        })->toArray();

        return new self($clientDTOs);
    }

    public function jsonSerialize(): array
    {
        return $this->clients;
    }
}
