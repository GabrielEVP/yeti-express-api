<?php

namespace App\Client\DTO;

final class FilterClientDTO
{
    public string $id;
    public string $registration_number;
    public string $legal_name;
    public ?string $type;
    public ?string $country;
    public ?float $tax_rate;
    public bool $allow_credit;
    public bool $can_delete;
    public bool $has_had_debt;

    public function __construct(array $data)
    {
        $this->id = (string)($data['id'] ?? '');
        $this->registration_number = (string)($data['registration_number'] ?? '');
        $this->legal_name = (string)($data['legal_name'] ?? '');
        $this->type = isset($data['type']) ? (string)$data['type'] : null;
        $this->country = isset($data['country']) ? (string)$data['country'] : null;
        $this->tax_rate = isset($data['tax_rate']) ? (float)$data['tax_rate'] : null;
        $this->allow_credit = (bool)($data['allow_credit'] ?? false);
        $this->can_delete = (bool)($data['can_delete'] ?? false);
        $this->has_had_debt = (bool)($data['has_had_debt'] ?? false);
    }
}
