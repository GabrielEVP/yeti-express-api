<?php

namespace Database\Factories;

use App\Client\Models\ClientPhone;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientPhoneFactory extends Factory
{
    protected $model = ClientPhone::class;

    public function definition(): array
    {
        return [
            'phone' => $this->faker->phoneNumber(),
            'client_id' => $this->faker->randomNumber(),
        ];
    }
}
