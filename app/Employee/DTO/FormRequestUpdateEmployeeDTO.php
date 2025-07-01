<?php

namespace App\Employee\DTO;

use JsonSerializable;

class FormRequestUpdateEmployeeDTO implements JsonSerializable
{
    public function __construct(
        public string $name,
        public string $email,
        public string $role,
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            role: $data['role'],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];
    }

    public function toArray(): array
    {
        return $this->toArray();
    }
}


