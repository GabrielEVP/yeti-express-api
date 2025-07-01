<?php

namespace App\Debt\DTO;

use JsonSerializable;

class FilterDebtByStatusDTO implements JsonSerializable
{
    public function __construct(
        public string $clientId,
        public ?string $status = null,
        public int $page = 1,
        public int $perPage = 15
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            clientId: $request->route('client'),
            status: $request->query('status'),
            page: (int) $request->query('page', 1),
            perPage: (int) $request->query('per_page', 15)
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'client_id' => $this->clientId,
            'status' => $this->status,
            'page' => $this->page,
            'per_page' => $this->perPage
        ];
    }
}
