<?php

namespace Database\Factories;

use App\Models\DebtPayment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class DebtPaymentFactory extends Factory
{
    protected $model = DebtPayment::class;

    public function definition(): array
    {
        return [
            'date' => Carbon::now(),
            'amount' => $this->faker->randomFloat(),
            'method' => $this->faker->word(),
            'debt_id' => $this->faker->randomNumber(),
            'user_id' => $this->faker->randomNumber(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
