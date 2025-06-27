<?php

namespace Database\Factories;

use App\Models\Delivery;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class DeliveryFactory extends Factory
{
    protected $model = Delivery::class;

    public function definition(): array
    {
        return [
            'number' => $this->faker->word(),
            'date' => Carbon::now(),
            'status' => $this->faker->word(),
            'payment_type' => $this->faker->word(),
            'payment_status' => $this->faker->word(),
            'amount' => $this->faker->randomFloat(),
            'pickup_address' => $this->faker->address(),
            'cancellation_notes' => $this->faker->word(),
            'notes' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'service_id' => $this->faker->randomNumber(),
            'client_id' => $this->faker->randomNumber(),
            'courier_id' => $this->faker->randomNumber(),
            'user_id' => $this->faker->randomNumber(),
        ];
    }
}
