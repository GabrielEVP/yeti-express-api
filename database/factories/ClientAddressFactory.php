<?php

namespace Database\Factories;

use App\Models\ClientAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientAddressFactory extends Factory
{
    protected $model = ClientAddress::class;

    public function definition(): array
    {
        return [
            'address' => $this->faker->address(),
            'client_id' => $this->faker->randomNumber(),
        ];
    }
}
