<?php

namespace Database\Factories;

use App\Client\Models\ClientEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientEmailFactory extends Factory
{
    protected $model = ClientEmail::class;

    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'client_id' => $this->faker->randomNumber(),
        ];
    }
}
