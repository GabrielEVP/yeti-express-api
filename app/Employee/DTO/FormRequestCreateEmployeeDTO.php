<?php

namespace App\Employee\DTO;

use JsonSerializable;

class FormRequestCreateEmployeeDTO implements JsonSerializable
{
    public function __construct(
        public string $name,
        public string $email,
        public string $role,
        public string $password,
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            role: $data['role'],
            password: $data['password'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'password' => $this->password,
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];
    }


}


