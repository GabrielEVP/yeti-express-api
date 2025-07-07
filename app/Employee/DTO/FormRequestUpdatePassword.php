<?php

namespace App\Employee\DTO;

class FormRequestUpdatePassword
{
    public function __construct(public string $password)
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            password: $data['password'],
        );
    }

    public function toArray(): array
    {
        return [
            'password' => $this->password,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
