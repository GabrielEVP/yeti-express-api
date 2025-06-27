<?php

namespace Database\Factories;

use App\Models\DeliveryReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryReceiptFactory extends Factory
{
    protected $model = DeliveryReceipt::class;

    public function definition(): array
    {
        return [
            'full_name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'delivery_id' => $this->faker->randomNumber(),
        ];
    }
}
