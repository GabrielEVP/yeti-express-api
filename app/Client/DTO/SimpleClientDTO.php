<?php

namespace App\Client\DTO;

final class SimpleClientDTO
{
    public int $id;
    public string $legal_name;
    public ?string $type;
    public string $registration_number;
    public bool $can_delete;
    public ?int $allow_credit;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->legal_name = $data['legal_name'];
        $this->type = $data['type'] ?? null;
        $this->registration_number = $data['registration_number'];
        $this->can_delete = $data['can_delete'] ?? false;
        $this->allow_credit = $data['allow_credit'] ?? null;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'legal_name' => $this->legal_name,
            'type' => $this->type,
            'registration_number' => $this->registration_number,
            'can_delete' => $this->can_delete,
            'allow_credit' => $this->allow_credit,
        ];
    }

    public static function mapFromArray($row): self
    {
        return new self($row->toArray());
    }
}
