<?php

namespace App\Employee\DTO;

class SimpleEmployeeDTO implements \JsonSerializable
{
    public int $id;
    public string $name;
    public ?string $email;
    public ?string $role;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->email = $data['email'];
        $this->role = $data['role'];
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public static function fromCollection(array $employees): array
    {
        return array_map(fn($employee) => self::fromArray($employee), $employees);
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];
    }
}
