<?php

namespace App\Client\DTO;

use App\Client\Models\Client;
use App\Client\Models\Type;
use JsonSerializable;

class ClientDTO implements JsonSerializable
{
    public int $id;
    public string $legal_name;
    public ?Type $type;
    public string $registration_number;
    public ?string $notes;
    public ?int $allow_credit;
    public int $user_id;
    public string $created_at;
    public string $updated_at;
    public array $addresses = [];
    public array $phones = [];
    public array $emails = [];
    public array $events = [];

    public function __construct(Client $client)
    {
        $this->id = $client->id;
        $this->legal_name = $client->legal_name;
        $this->type = $client->type;
        $this->registration_number = $client->registration_number;
        $this->notes = $client->notes;
        $this->allow_credit = $client->allow_credit ?? 0;
        $this->user_id = $client->user_id;
        $this->created_at = $client->created_at->toDateTimeString();
        $this->updated_at = $client->updated_at->toDateTimeString();

        foreach ($client->addresses ?? [] as $address) {
            $this->addresses[] = [
                'id' => $address->id,
                'address' => $address->address,
            ];
        }

        foreach ($client->phones ?? [] as $phone) {
            $this->phones[] = [
                'id' => $phone->id,
                'phone' => $phone->phone,
            ];
        }

        foreach ($client->emails ?? [] as $email) {
            $this->emails[] = [
                'id' => $email->id,
                'email' => $email->email,
            ];
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'legal_name' => $this->legal_name,
            'type' => $this->type?->value,
            'registration_number' => $this->registration_number,
            'notes' => $this->notes,
            'allow_credit' => $this->allow_credit,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'addresses' => $this->addresses,
            'phones' => $this->phones,
            'emails' => $this->emails,
        ];
    }
}
